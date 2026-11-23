package mphf

import (
	"bytes"
	"crypto/sha256"
	"encoding/binary"
	"math/bits"
	"runtime"
)

// OpenOptions configure Open.
type OpenOptions struct {
	// MadviseRandom calls madvise(MADV_RANDOM) on the values section (useful when the file lives on disk).
	MadviseRandom bool
	// SkipHashCheck skips the SHA-256 check of the content (reads the whole file, so it takes some time on large
	// files). The length is always checked. Only for callers who verified the file before.
	SkipHashCheck bool
}

// Info describes a loaded table.
type Info struct {
	CreatedAtMs   uint64  `json:"createdAtMs"`
	WatermarkMs   uint64  `json:"watermarkMs"`
	KeyCount      uint64  `json:"keyCount"`
	PlacedCount   uint64  `json:"placedCount"`
	FallbackCount uint64  `json:"fallbackCount"`
	TypeCount     int     `json:"typeCount"`
	ValueWidth    int     `json:"valueWidth"`
	Levels        int     `json:"levels"`
	Gamma         float64 `json:"gamma"`
	Bytes         int     `json:"bytes"`
	Generator     string  `json:"generator"`
	GeneratedOn   string  `json:"generatedOn"`
}

// Table is an opened, memory mapped `.mphf` file. All methods are safe for concurrent use. The mapping is released
// by a runtime cleanup once the Table is unreachable (or by Close).
type Table struct {
	mapping []byte // the whole file, as mapped
	data    []byte // the content, i.e. the file without its preface
	pre     *preamble
	hdr     *header
	key     hashKey

	types  []string // copied to the Go heap, never pointing into the mapping
	levels []levelInfo

	bitsOff, rankOff, valuesOff, fallbackOff int
	width                                    uint
	mask                                     uint64

	hashVerified bool

	cleanup runtime.Cleanup
}

// Open maps path read-only and validates it: the preface, the length of the file, the SHA-256 of the content (unless
// opts.SkipHashCheck) and the structure of the content.
func Open(path string, opts OpenOptions) (*Table, error) {
	mapping, err := mapFile(path)
	if err != nil {
		return nil, err
	}
	t, err := parseFileGuarded(mapping, !opts.SkipHashCheck)
	if err != nil {
		_ = unmap(mapping)
		return nil, err
	}
	t.mapping = mapping
	if opts.MadviseRandom {
		adviseRandom(mapping, t.pre.contentOffset+int(t.hdr.sections[secValues].offset), int(t.hdr.sections[secValues].length))
	}
	t.cleanup = runtime.AddCleanup(t, func(d []byte) { _ = unmap(d) }, mapping)
	return t, nil
}

// parseFile validates a complete file image (see Open); t.mapping is left to the caller.
func parseFile(file []byte, verifyHash bool) (*Table, error) {
	pre, err := parsePreamble(file)
	if err != nil {
		return nil, err
	}
	content := file[pre.contentOffset:]
	if uint64(len(content)) != pre.contentLength {
		return nil, formatErr("content has %d bytes, the preface says %d (truncated or extended file)", len(content), pre.contentLength)
	}
	if verifyHash {
		if sum := sha256.Sum256(content); !bytes.Equal(sum[:], pre.sha256[:]) {
			return nil, formatErr("checksum mismatch: computed %x, stored %x", sum, pre.sha256)
		}
	}
	t, err := parseTable(content)
	if err != nil {
		return nil, err
	}
	t.pre = pre
	t.hashVerified = verifyHash
	return t, nil
}

// Close unmaps the file immediately. It must not be called while other goroutines still use the Table. Normal
// users never call it, the mapping is released automatically.
func (t *Table) Close() error {
	t.cleanup.Stop()
	d := t.mapping
	t.mapping, t.data = nil, nil
	if d == nil {
		return nil
	}
	return unmap(d)
}

func parseTable(data []byte) (*Table, error) {
	size := uint64(len(data))
	h, err := decodeHeader(data)
	if err != nil {
		return nil, err
	}
	if h.valueWidth < 1 || h.valueWidth > 16 {
		return nil, formatErr("value width %d out of range", h.valueWidth)
	}
	if h.levelCount > maxLevelCount {
		return nil, formatErr("level count %d too large", h.levelCount)
	}
	for i, s := range h.sections {
		if s.offset > size || s.length > size-s.offset {
			return nil, formatErr("section %d out of bounds", i)
		}
	}
	if h.placedCount > size*8 || h.fallbackCount > size {
		return nil, formatErr("key counts exceed file size")
	}
	if h.keyCount != h.placedCount+h.fallbackCount {
		return nil, formatErr("key count %d != placed %d + fallback %d", h.keyCount, h.placedCount, h.fallbackCount)
	}

	t := &Table{data: data, hdr: h, key: newHashKey(h.hashKey), width: uint(h.valueWidth)}
	t.mask = uint64(1)<<t.width - 1

	// dictionary (copied to the Go heap)
	ds := h.sections[secDict]
	if ds.length < 4 {
		return nil, formatErr("dictionary too short")
	}
	dict := data[ds.offset : ds.offset+ds.length]
	typeCount := binary.LittleEndian.Uint32(dict)
	if typeCount > MaxTypes {
		return nil, formatErr("type count %d too large", typeCount)
	}
	pos := 4
	t.types = make([]string, 0, typeCount)
	for i := uint32(0); i < typeCount; i++ {
		if pos >= len(dict) {
			return nil, formatErr("dictionary truncated")
		}
		l := int(dict[pos])
		pos++
		if l == 0 || pos+l > len(dict) {
			return nil, formatErr("dictionary entry %d invalid", i)
		}
		t.types = append(t.types, string(dict[pos:pos+l])) // copy
		pos += l
	}
	if pos != len(dict) {
		return nil, formatErr("dictionary has %d trailing bytes", len(dict)-pos)
	}
	if h.valueWidth != valueWidthFor(int(typeCount)) {
		return nil, formatErr("value width %d does not match %d types", h.valueWidth, typeCount)
	}

	// levels
	ls := h.sections[secLevels]
	if ls.length != uint64(h.levelCount)*levelRec {
		return nil, formatErr("level table has wrong size")
	}
	var totalBits uint64
	t.levels = make([]levelInfo, h.levelCount)
	for i := range t.levels {
		rec := data[ls.offset+uint64(i)*levelRec:]
		l := levelInfo{bitOffset: binary.LittleEndian.Uint64(rec), size: binary.LittleEndian.Uint64(rec[8:])}
		if l.bitOffset != totalBits || l.size == 0 || l.size%64 != 0 || l.size > size*8 {
			return nil, formatErr("level %d has invalid geometry", i)
		}
		totalBits += l.size
		t.levels[i] = l
	}
	bs := h.sections[secBits]
	if bs.length*8 != totalBits {
		return nil, formatErr("bit vector section has %d bits, levels describe %d", bs.length*8, totalBits)
	}

	// rank checkpoints and key count
	words := totalBits / 64
	rs := h.sections[secRank]
	if rs.length != ceilDiv(words, rankBlockWord)*8 {
		return nil, formatErr("rank section has wrong size")
	}
	t.bitsOff, t.rankOff = int(bs.offset), int(rs.offset)
	if words > 0 {
		lastBlk := (words - 1) / rankBlockWord
		total := binary.LittleEndian.Uint64(data[t.rankOff+int(lastBlk)*8:])
		for wi := lastBlk * rankBlockWord; wi < words; wi++ {
			total += uint64(bits.OnesCount64(binary.LittleEndian.Uint64(data[t.bitsOff+int(wi)*8:])))
		}
		if total != h.placedCount {
			return nil, formatErr("bit vectors contain %d keys, header says %d", total, h.placedCount)
		}
	} else if h.placedCount != 0 {
		return nil, formatErr("no bit vectors but %d placed keys", h.placedCount)
	}

	// values
	vs := h.sections[secValues]
	if vs.length != ceilDiv(h.placedCount*uint64(h.valueWidth), 8)+8 {
		return nil, formatErr("value section has wrong size")
	}
	t.valuesOff = int(vs.offset)

	// fallback
	fs := h.sections[secFallback]
	if fs.length != 8+h.fallbackCount*fallbackRec {
		return nil, formatErr("fallback section has wrong size")
	}
	if binary.LittleEndian.Uint64(data[fs.offset:]) != h.fallbackCount {
		return nil, formatErr("fallback count mismatch")
	}
	t.fallbackOff = int(fs.offset) + 8

	// canonical layout, which also pins the total file size
	var lengths [secCount]uint64
	for i, s := range h.sections {
		lengths[i] = s.length
	}
	canon, total := layout(lengths)
	if canon != h.sections {
		return nil, formatErr("section table is not canonical")
	}
	if total != size {
		return nil, formatErr("content size %d, expected %d", size, total)
	}
	return t, nil
}

// Info returns a description of the table.
func (t *Table) Info() Info {
	defer runtime.KeepAlive(t)
	return Info{
		CreatedAtMs:   t.hdr.createdAtMs,
		WatermarkMs:   t.hdr.watermarkMs,
		KeyCount:      t.hdr.keyCount,
		PlacedCount:   t.hdr.placedCount,
		FallbackCount: t.hdr.fallbackCount,
		TypeCount:     len(t.types),
		ValueWidth:    int(t.hdr.valueWidth),
		Levels:        len(t.levels),
		Gamma:         float64(t.hdr.gammaMilli) / 1000,
		Bytes:         len(t.mapping),
		Generator:     t.pre.generator,
		GeneratedOn:   t.pre.generatedOn,
	}
}

// Types returns a copy of the type dictionary.
func (t *Table) Types() []string { return append([]string(nil), t.types...) }

// Lookup returns the type of id. Ids that were not part of the build usually return some (wrong) type, as the
// structure has no membership information; callers must treat the result as a candidate only. ok is false if no
// type could be determined.
func (t *Table) Lookup(id string) (typ string, ok bool) {
	h0, h1 := t.key.sum128(id)
	idx, ok := t.lookupHash(h0, h1)
	runtime.KeepAlive(t)
	if !ok || idx >= len(t.types) {
		return "", false
	}
	return t.types[idx], true
}

func (t *Table) lookupHash(h0, h1 uint64) (int, bool) {
	data := t.data
	for li := range t.levels {
		l := &t.levels[li]
		g := l.bitOffset + levelPos(h0, h1, uint32(li), l.size)
		word := binary.LittleEndian.Uint64(data[t.bitsOff+int(g>>6)*8:])
		if word>>(g&63)&1 == 0 {
			continue
		}
		r := t.rank(g)
		if r >= t.hdr.placedCount { // only possible with corrupt rank checkpoints (checked by Verify)
			return 0, false
		}
		return int(t.value(r)), true
	}
	return t.searchFallback(h0, h1)
}

func (t *Table) rank(g uint64) uint64 {
	data := t.data
	word := g >> 6
	blk := word / rankBlockWord
	r := binary.LittleEndian.Uint64(data[t.rankOff+int(blk)*8:])
	for wi := blk * rankBlockWord; wi < word; wi++ {
		r += uint64(bits.OnesCount64(binary.LittleEndian.Uint64(data[t.bitsOff+int(wi)*8:])))
	}
	last := binary.LittleEndian.Uint64(data[t.bitsOff+int(word)*8:])
	return r + uint64(bits.OnesCount64(last&(uint64(1)<<(g&63)-1)))
}

func (t *Table) value(idx uint64) uint64 {
	bit := idx * uint64(t.width)
	v := binary.LittleEndian.Uint64(t.data[t.valuesOff+int(bit>>3):])
	return v >> (bit & 7) & t.mask
}

func (t *Table) searchFallback(h0, h1 uint64) (int, bool) {
	data := t.data
	lo, hi := 0, int(t.hdr.fallbackCount)
	for lo < hi {
		mid := int(uint(lo+hi) >> 1)
		rec := data[t.fallbackOff+mid*fallbackRec:]
		r0, r1 := binary.LittleEndian.Uint64(rec), binary.LittleEndian.Uint64(rec[8:])
		switch {
		case r0 == h0 && r1 == h1:
			return int(binary.LittleEndian.Uint16(rec[16:])), true
		case r0 < h0 || (r0 == h0 && r1 < h1):
			lo = mid + 1
		default:
			hi = mid
		}
	}
	return 0, false
}

// Verify runs a complete check of the file: structure, SHA-256 of the content, rank checkpoints, sorted fallback
// table and value range. It reads the whole file.
func (t *Table) Verify() error {
	defer runtime.KeepAlive(t)
	if !t.hashVerified {
		if sum := sha256.Sum256(t.data); !bytes.Equal(sum[:], t.pre.sha256[:]) {
			return formatErr("checksum mismatch: computed %x, stored %x", sum, t.pre.sha256)
		}
		t.hashVerified = true
	}
	words := len(t.bitsWords()) / 8
	var total uint64
	for blk := 0; blk*rankBlockWord < words; blk++ {
		if got := binary.LittleEndian.Uint64(t.data[t.rankOff+blk*8:]); got != total {
			return formatErr("rank checkpoint %d is %d, expected %d", blk, got, total)
		}
		for wi := blk * rankBlockWord; wi < min((blk+1)*rankBlockWord, words); wi++ {
			total += uint64(bits.OnesCount64(binary.LittleEndian.Uint64(t.data[t.bitsOff+wi*8:])))
		}
	}
	for i := uint64(0); i < t.hdr.placedCount; i++ {
		if v := t.value(i); v >= uint64(len(t.types)) {
			return formatErr("value %d at index %d is not a known type", v, i)
		}
	}
	var p0, p1 uint64
	for i := 0; i < int(t.hdr.fallbackCount); i++ {
		rec := t.data[t.fallbackOff+i*fallbackRec:]
		r0, r1 := binary.LittleEndian.Uint64(rec), binary.LittleEndian.Uint64(rec[8:])
		if i > 0 && !(r0 > p0 || (r0 == p0 && r1 > p1)) {
			return formatErr("fallback table is not strictly sorted at record %d", i)
		}
		if int(binary.LittleEndian.Uint16(rec[16:])) >= len(t.types) {
			return formatErr("fallback record %d has unknown type", i)
		}
		p0, p1 = r0, r1
	}
	return nil
}

func (t *Table) bitsWords() []byte {
	return t.data[t.bitsOff : t.bitsOff+int(t.hdr.sections[secBits].length)]
}

// VerifyFile opens path, runs Table.Verify and returns the table info.
func VerifyFile(path string) (*Info, error) {
	t, err := Open(path, OpenOptions{})
	if err != nil {
		return nil, err
	}
	defer t.Close()
	if err := t.Verify(); err != nil {
		return nil, err
	}
	info := t.Info()
	return &info, nil
}
