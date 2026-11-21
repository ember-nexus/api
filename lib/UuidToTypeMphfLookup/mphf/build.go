package mphf

import (
	"bufio"
	"bytes"
	"crypto/rand"
	"crypto/sha256"
	"encoding/binary"
	"errors"
	"fmt"
	"io"
	"math"
	"math/bits"
	"os"
	"path/filepath"
	"sort"
	"time"
)

// BuildOptions configure a Builder. The zero value is valid.
type BuildOptions struct {
	// Gamma is the BBHash space factor (bits per remaining key and level); default DefaultGamma.
	Gamma float64
	// MaxLevels is the maximum number of levels; keys that still collide go to the fallback table. Default 32.
	MaxLevels int
	// WatermarkMs is stored in the header (the overlay switch time of the build).
	WatermarkMs uint64
	// CreatedAtMs is stored in the header; defaults to the current time.
	CreatedAtMs uint64
	// HashKey is the SipHash key; default is a fresh random key (crypto/rand). Only tests should set it.
	HashKey *[16]byte
	// Generator is written to the preface of the file, e.g. "Ember Nexus API 0.2.0"; default DefaultGenerator.
	Generator string
	// ExpectedKeys pre-sizes the key storage. An upper bound is fine, untouched memory is never resident.
	ExpectedKeys int
}

// Builder collects (id, type) pairs and builds a `.mphf` file from them.
//
// Memory: 18 bytes per key (128 bit hash + 16 bit type index) plus the bit vectors during Build.
type Builder struct {
	opts   BuildOptions
	key    hashKey
	keyRaw [16]byte
	hashes []uint64 // two words per key
	types  []uint16
	names  []string
	byName map[string]uint16
	built  bool
	input  int64
}

// NewBuilder creates a Builder.
func NewBuilder(opts BuildOptions) (*Builder, error) {
	if opts.Gamma == 0 {
		opts.Gamma = DefaultGamma
	}
	if opts.Gamma < 1.0 || opts.Gamma > 16 || math.IsNaN(opts.Gamma) {
		return nil, fmt.Errorf("gamma must be between 1 and 16, got %v", opts.Gamma)
	}
	if opts.MaxLevels == 0 {
		opts.MaxLevels = DefaultMaxLevels
	}
	if opts.MaxLevels < 1 || opts.MaxLevels > maxLevelCount {
		return nil, fmt.Errorf("max levels must be between 1 and %d, got %d", maxLevelCount, opts.MaxLevels)
	}
	if opts.Generator == "" {
		opts.Generator = DefaultGenerator
	}
	if err := validateGenerator(opts.Generator); err != nil {
		return nil, err
	}
	if opts.CreatedAtMs == 0 {
		opts.CreatedAtMs = uint64(time.Now().UnixMilli())
	}
	b := &Builder{opts: opts, byName: map[string]uint16{}}
	if opts.HashKey != nil {
		b.keyRaw = *opts.HashKey
	} else if _, err := rand.Read(b.keyRaw[:]); err != nil {
		return nil, fmt.Errorf("unable to create random hash key: %w", err)
	}
	b.key = newHashKey(b.keyRaw)
	if opts.ExpectedKeys > 0 {
		b.hashes = make([]uint64, 0, 2*opts.ExpectedKeys)
		b.types = make([]uint16, 0, opts.ExpectedKeys)
	}
	return b, nil
}

// Add adds one id with its type. The id is taken as is (see ValidateID for the canonical form).
func (b *Builder) Add(id, typ string) error {
	if id == "" {
		return errors.New("id is empty")
	}
	if err := ValidateTypeName(typ); err != nil {
		return err
	}
	idx, err := b.typeIndex(typ)
	if err != nil {
		return err
	}
	b.push(id, idx)
	return nil
}

func (b *Builder) addBytes(id string, typ []byte) error {
	if idx, ok := b.byName[string(typ)]; ok { // no allocation
		b.push(id, idx)
		return nil
	}
	return b.Add(id, string(typ))
}

func (b *Builder) typeIndex(typ string) (uint16, error) {
	if idx, ok := b.byName[typ]; ok {
		return idx, nil
	}
	if len(b.names) >= MaxTypes {
		return 0, fmt.Errorf("more than %d distinct types", MaxTypes)
	}
	idx := uint16(len(b.names))
	b.names = append(b.names, typ)
	b.byName[typ] = idx
	return idx, nil
}

func (b *Builder) push(id string, idx uint16) {
	if keyHook != nil {
		id = keyHook(id)
	}
	h0, h1 := b.key.sum128(id)
	b.hashes = append(b.hashes, h0, h1)
	b.types = append(b.types, idx)
}

// Len returns the number of added pairs (including duplicates).
func (b *Builder) Len() int { return len(b.types) }

// ConflictError is returned by Build if the same id was added with different types.
type ConflictError struct {
	// Hashes are the 128 bit key hashes (under the build's secret key) of the conflicting ids.
	Hashes [][2]uint64
	hasher hashKey
}

func (e *ConflictError) Error() string {
	return fmt.Sprintf("%d id(s) occur with conflicting types", len(e.Hashes))
}

// maxConflictLineSize bounds the lines FindIDs looks at; a valid line is `<37 byte id>;<type>` and far shorter.
const maxConflictLineSize = 1 << 12

// FindIDs scans a CSV stream again and returns the ids (with their types) belonging to the conflicting hashes. Lines
// longer than maxConflictLineSize can not be valid and are skipped.
func (e *ConflictError) FindIDs(r io.Reader, limit int) ([]string, error) {
	want := make(map[[2]uint64]struct{}, len(e.Hashes))
	for _, h := range e.Hashes {
		want[h] = struct{}{}
	}
	var out []string
	br := bufio.NewReaderSize(r, maxConflictLineSize)
	for len(out) < limit {
		line, err := br.ReadSlice('\n')
		if errors.Is(err, bufio.ErrBufferFull) {
			// skip the rest of the oversized line
			for errors.Is(err, bufio.ErrBufferFull) {
				_, err = br.ReadSlice('\n')
			}
			if err != nil {
				break
			}
			continue
		}
		line = bytes.TrimSuffix(line, []byte("\n"))
		if sep := bytes.IndexByte(line, ';'); sep > 0 {
			h0, h1 := e.hasher.sum128(string(line[:sep]))
			if _, ok := want[[2]uint64{h0, h1}]; ok {
				out = append(out, string(line))
			}
		}
		if err != nil {
			break
		}
	}
	return out, nil
}

// Stats describes a finished build.
type Stats struct {
	InputCount    int64   `json:"inputCount"`
	KeyCount      uint64  `json:"keyCount"`
	TypeCount     int     `json:"typeCount"`
	ValueWidth    int     `json:"valueWidth"`
	Levels        int     `json:"levels"`
	FallbackCount uint64  `json:"fallbackCount"`
	Bytes         uint64  `json:"bytes"`
	BitsPerKey    float64 `json:"bitsPerKey"`
	DurationMs    int64   `json:"durationMs"`
}

type levelInfo struct {
	bitOffset uint64
	size      uint64
}

type fbRecord struct {
	h0, h1 uint64
	typ    uint16
}

// image is the fully computed, not yet written file.
type image struct {
	hdr      header
	dict     []byte
	levels   []levelInfo
	bitWords []uint64
	rank     []uint64
	values   []byte
	fallback []fbRecord
	// contentSize is the size of the binary content, without the preface.
	contentSize uint64
}

// Build computes the MPHF and writes it to w. The builder must not be used afterwards.
func (b *Builder) Build(w io.Writer) (*Stats, error) {
	start := time.Now()
	img, err := b.compute()
	if err != nil {
		return nil, err
	}
	size, err := img.write(w, b.opts)
	if err != nil {
		return nil, err
	}
	return b.stats(img, size, time.Since(start)), nil
}

// BuildFile writes `<path>.tmp`, fsyncs it and renames it to path.
func (b *Builder) BuildFile(path string) (*Stats, error) {
	start := time.Now()
	img, err := b.compute()
	if err != nil {
		return nil, err
	}
	tmp := path + ".tmp"
	// finished files are read-only, so nothing in the container writes into a file which is mapped; a leftover of a
	// crashed build is read-only as well and has to be removed first
	_ = os.Remove(tmp)
	f, err := os.OpenFile(tmp, os.O_WRONLY|os.O_CREATE|os.O_EXCL, 0o444)
	if err != nil {
		return nil, err
	}
	bw := bufio.NewWriterSize(f, 1<<20)
	size, werr := img.write(bw, b.opts)
	if werr == nil {
		werr = bw.Flush()
	}
	if werr == nil {
		werr = f.Sync()
	}
	if cerr := f.Close(); werr == nil {
		werr = cerr
	}
	if werr != nil {
		_ = os.Remove(tmp)
		return nil, werr
	}
	if err := os.Rename(tmp, path); err != nil {
		_ = os.Remove(tmp)
		return nil, err
	}
	if d, err := os.Open(filepath.Dir(path)); err == nil {
		_ = d.Sync()
		_ = d.Close()
	}
	return b.stats(img, size, time.Since(start)), nil
}

func (b *Builder) stats(img *image, fileSize uint64, d time.Duration) *Stats {
	s := &Stats{
		InputCount:    b.input,
		KeyCount:      img.hdr.keyCount,
		TypeCount:     len(b.names),
		ValueWidth:    int(img.hdr.valueWidth),
		Levels:        len(img.levels),
		FallbackCount: img.hdr.fallbackCount,
		Bytes:         fileSize,
		DurationMs:    d.Milliseconds(),
	}
	if s.KeyCount > 0 {
		s.BitsPerKey = float64(fileSize) * 8 / float64(s.KeyCount)
	}
	return s
}

func (b *Builder) swap(i, j int) {
	b.hashes[2*i], b.hashes[2*j] = b.hashes[2*j], b.hashes[2*i]
	b.hashes[2*i+1], b.hashes[2*j+1] = b.hashes[2*j+1], b.hashes[2*i+1]
	b.types[i], b.types[j] = b.types[j], b.types[i]
}

func (b *Builder) compute() (*image, error) {
	if b.built {
		return nil, errors.New("builder was already used")
	}
	b.built = true

	// Sort the type dictionary, so the output only depends on the input set and the hash key.
	order := make([]int, len(b.names))
	for i := range order {
		order[i] = i
	}
	sort.Slice(order, func(x, y int) bool { return b.names[order[x]] < b.names[order[y]] })
	remap := make([]uint16, len(b.names))
	sorted := make([]string, len(b.names))
	for newIdx, oldIdx := range order {
		remap[oldIdx] = uint16(newIdx)
		sorted[newIdx] = b.names[oldIdx]
	}
	for i, t := range b.types {
		b.types[i] = remap[t]
	}

	img := &image{}
	n := len(b.types)
	b.input = int64(n)
	m := n
	type placedRange struct{ lo, hi int }
	var ranges []placedRange
	gamma := b.opts.Gamma

	for level := 0; level < b.opts.MaxLevels && m > 0; level++ {
		size := alignUp(uint64(math.Ceil(gamma*float64(m))), 64)
		if size < 64 {
			size = 64
		}
		words := size / 64
		a := make([]uint64, words)
		c := make([]uint64, words)
		lv := uint32(level)
		for i := 0; i < m; i++ {
			p := levelPos(b.hashes[2*i], b.hashes[2*i+1], lv, size)
			wi, bit := p>>6, uint64(1)<<(p&63)
			if c[wi]&bit != 0 {
				continue
			}
			if a[wi]&bit != 0 {
				a[wi] &^= bit
				c[wi] |= bit
			} else {
				a[wi] |= bit
			}
		}
		c = nil
		i, j := 0, m
		for i < j {
			p := levelPos(b.hashes[2*i], b.hashes[2*i+1], lv, size)
			if a[p>>6]>>(p&63)&1 == 1 {
				j--
				b.swap(i, j)
			} else {
				i++
			}
		}
		img.levels = append(img.levels, levelInfo{bitOffset: uint64(len(img.bitWords)) * 64, size: size})
		img.bitWords = append(img.bitWords, a...)
		ranges = append(ranges, placedRange{lo: j, hi: m})
		m = j
	}

	// fallback table: everything still unplaced (including duplicate ids, which always collide)
	fb := make([]fbRecord, m)
	for i := 0; i < m; i++ {
		fb[i] = fbRecord{h0: b.hashes[2*i], h1: b.hashes[2*i+1], typ: b.types[i]}
	}
	sort.Slice(fb, func(x, y int) bool {
		if fb[x].h0 != fb[y].h0 {
			return fb[x].h0 < fb[y].h0
		}
		return fb[x].h1 < fb[y].h1
	})
	conflict := &ConflictError{hasher: b.key}
	dedup := fb[:0]
	for _, r := range fb {
		if k := len(dedup); k > 0 && dedup[k-1].h0 == r.h0 && dedup[k-1].h1 == r.h1 {
			if dedup[k-1].typ != r.typ {
				if len(conflict.Hashes) == 0 || conflict.Hashes[len(conflict.Hashes)-1] != [2]uint64{r.h0, r.h1} {
					conflict.Hashes = append(conflict.Hashes, [2]uint64{r.h0, r.h1})
				}
			}
			continue
		}
		dedup = append(dedup, r)
	}
	if len(conflict.Hashes) > 0 {
		return nil, conflict
	}
	img.fallback = dedup

	// rank checkpoints
	img.rank = make([]uint64, ceilDiv(uint64(len(img.bitWords)), rankBlockWord))
	var total uint64
	for blk := range img.rank {
		img.rank[blk] = total
		end := min((blk+1)*rankBlockWord, len(img.bitWords))
		for _, wd := range img.bitWords[blk*rankBlockWord : end] {
			total += uint64(bits.OnesCount64(wd))
		}
	}
	placed := total

	// packed values in MPHF index order
	width := uint(valueWidthFor(len(sorted)))
	img.values = make([]byte, ceilDiv(placed*uint64(width), 8)+8)
	for li, r := range ranges {
		lv := img.levels[li]
		for k := r.lo; k < r.hi; k++ {
			g := lv.bitOffset + levelPos(b.hashes[2*k], b.hashes[2*k+1], uint32(li), lv.size)
			idx := rankLocal(img.bitWords, img.rank, g)
			bit := idx * uint64(width)
			byteOff, shift := bit>>3, bit&7
			cur := binary.LittleEndian.Uint64(img.values[byteOff:])
			binary.LittleEndian.PutUint64(img.values[byteOff:], cur|uint64(b.types[k])<<shift)
		}
	}
	// free the key storage, the file is fully described by img
	b.hashes, b.types = nil, nil

	// dictionary
	var dict bytes.Buffer
	_ = binary.Write(&dict, binary.LittleEndian, uint32(len(sorted)))
	for _, name := range sorted {
		dict.WriteByte(byte(len(name)))
		dict.WriteString(name)
	}
	img.dict = dict.Bytes()
	b.names = sorted

	lengths := [secCount]uint64{
		secDict:     uint64(len(img.dict)),
		secLevels:   uint64(len(img.levels)) * levelRec,
		secBits:     uint64(len(img.bitWords)) * 8,
		secRank:     uint64(len(img.rank)) * 8,
		secValues:   uint64(len(img.values)),
		secFallback: 8 + uint64(len(img.fallback))*fallbackRec,
	}
	sections, contentSize := layout(lengths)
	img.contentSize = contentSize
	img.hdr = header{
		formatVersion: FormatVersion,
		algorithm:     AlgorithmBBHashSipHash24x128,
		createdAtMs:   b.opts.CreatedAtMs,
		watermarkMs:   b.opts.WatermarkMs,
		keyCount:      placed + uint64(len(img.fallback)),
		hashKey:       b.keyRaw,
		gammaMilli:    uint32(math.Round(gamma * 1000)),
		levelCount:    uint32(len(img.levels)),
		valueWidth:    uint8(width),
		placedCount:   placed,
		fallbackCount: uint64(len(img.fallback)),
		sections:      sections,
	}
	return img, nil
}

func rankLocal(words, ranks []uint64, g uint64) uint64 {
	word := g >> 6
	blk := word / rankBlockWord
	r := ranks[blk]
	for i := blk * rankBlockWord; i < word; i++ {
		r += uint64(bits.OnesCount64(words[i]))
	}
	return r + uint64(bits.OnesCount64(words[word]&(uint64(1)<<(g&63)-1)))
}

// write serialises the image: the preface (which contains the SHA-256 of the content, so the content is serialised
// twice, once into the hash) followed by the content. It returns the size of the whole file.
func (img *image) write(w io.Writer, opts BuildOptions) (uint64, error) {
	h := sha256.New()
	if err := img.writeContent(h); err != nil {
		return 0, err
	}
	var sum [sha256.Size]byte
	h.Sum(sum[:0])
	pre, err := encodePreamble(opts.Generator, opts.CreatedAtMs, img.contentSize, sum)
	if err != nil {
		return 0, err
	}
	if _, err := w.Write(pre); err != nil {
		return 0, err
	}
	if err := img.writeContent(w); err != nil {
		return 0, err
	}
	return uint64(len(pre)) + img.contentSize, nil
}

// writeContent serialises the binary content (header and sections).
func (img *image) writeContent(w io.Writer) error {
	cw := &countWriter{w: w}
	if _, err := cw.Write(img.hdr.encode()); err != nil {
		return err
	}
	pad := func() error {
		if rem := cw.n % sectionAlign; rem != 0 {
			_, err := cw.Write(make([]byte, sectionAlign-rem))
			return err
		}
		return nil
	}
	put := func(p []byte) error {
		if _, err := cw.Write(p); err != nil {
			return err
		}
		return pad()
	}
	if err := put(img.dict); err != nil {
		return err
	}
	lv := make([]byte, 0, len(img.levels)*levelRec)
	for _, l := range img.levels {
		lv = binary.LittleEndian.AppendUint64(lv, l.bitOffset)
		lv = binary.LittleEndian.AppendUint64(lv, l.size)
	}
	if err := put(lv); err != nil {
		return err
	}
	if err := writeWords(cw, img.bitWords); err != nil {
		return err
	}
	if err := pad(); err != nil {
		return err
	}
	if err := writeWords(cw, img.rank); err != nil {
		return err
	}
	if err := pad(); err != nil {
		return err
	}
	if err := put(img.values); err != nil {
		return err
	}
	fb := make([]byte, 0, 8+len(img.fallback)*fallbackRec)
	fb = binary.LittleEndian.AppendUint64(fb, uint64(len(img.fallback)))
	for _, r := range img.fallback {
		fb = binary.LittleEndian.AppendUint64(fb, r.h0)
		fb = binary.LittleEndian.AppendUint64(fb, r.h1)
		fb = binary.LittleEndian.AppendUint16(fb, r.typ)
	}
	if err := put(fb); err != nil {
		return err
	}
	if cw.n != img.contentSize {
		return fmt.Errorf("internal error: wrote %d content bytes, expected %d", cw.n, img.contentSize)
	}
	return nil
}

type countWriter struct {
	w io.Writer
	n uint64
}

func (c *countWriter) Write(p []byte) (int, error) {
	n, err := c.w.Write(p)
	c.n += uint64(n)
	return n, err
}

func writeWords(w io.Writer, words []uint64) error {
	buf := make([]byte, 0, 1<<16)
	for _, wd := range words {
		buf = binary.LittleEndian.AppendUint64(buf, wd)
		if len(buf) == cap(buf) {
			if _, err := w.Write(buf); err != nil {
				return err
			}
			buf = buf[:0]
		}
	}
	_, err := w.Write(buf)
	return err
}
