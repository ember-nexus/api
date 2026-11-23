package mphf

import (
	"bytes"
	"crypto/sha256"
	"encoding/hex"
	"errors"
	"fmt"
	"io"
	"math/rand/v2"
	"os"
	"path/filepath"
	"strings"
	"testing"
)

const alphabet = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz"

// testIDs creates n distinct sv2a7 shaped ids (constant block, slowly growing timestamp-like prefix, random rest).
func testIDs(rng *rand.Rand, n int) []string {
	seen := make(map[string]struct{}, n)
	out := make([]string, 0, n)
	for len(out) < n {
		var sb strings.Builder
		sb.WriteString("02IiNr")
		ts := uint64(1_760_000_000_000) + uint64(len(out))*37
		for i := 0; i < 8; i++ {
			sb.WriteByte(alphabet[ts%62])
			ts /= 62
		}
		for i := 0; i < 23; i++ {
			sb.WriteByte(alphabet[rng.IntN(62)])
		}
		id := sb.String()
		if _, dup := seen[id]; dup {
			continue
		}
		seen[id] = struct{}{}
		out = append(out, id)
	}
	return out
}

func typeNames(n int) []string {
	out := make([]string, n)
	for i := range out {
		out[i] = fmt.Sprintf("TYPE_%03d", i)
	}
	return out
}

func buildTo(t *testing.T, b *Builder) (string, *Stats) {
	t.Helper()
	path := filepath.Join(t.TempDir(), "test.mphf")
	st, err := b.BuildFile(path)
	if err != nil {
		t.Fatal(err)
	}
	return path, st
}

func newTestBuilder(t *testing.T, opts BuildOptions) *Builder {
	t.Helper()
	b, err := NewBuilder(opts)
	if err != nil {
		t.Fatal(err)
	}
	return b
}

func testRoundtrip(t *testing.T, n, types int) {
	rng := rand.New(rand.NewPCG(1, uint64(types)))
	ids := testIDs(rng, n)
	names := typeNames(types)
	want := make(map[string]string, n)
	b := newTestBuilder(t, BuildOptions{ExpectedKeys: n})
	for _, id := range ids {
		typ := names[rng.IntN(types)]
		want[id] = typ
		if err := b.Add(id, typ); err != nil {
			t.Fatal(err)
		}
	}
	path, st := buildTo(t, b)
	t.Logf("n=%d types=%d: %d bytes, %.2f bits/key, %d levels, %d fallback, w=%d", n, types, st.Bytes, st.BitsPerKey, st.Levels, st.FallbackCount, st.ValueWidth)
	if st.KeyCount != uint64(n) {
		t.Fatalf("key count %d", st.KeyCount)
	}
	if _, err := VerifyFile(path); err != nil {
		t.Fatal(err)
	}
	tab, err := Open(path, OpenOptions{MadviseRandom: true})
	if err != nil {
		t.Fatal(err)
	}
	defer tab.Close()
	for id, typ := range want {
		got, ok := tab.Lookup(id)
		if !ok || got != typ {
			t.Fatalf("id %s: got %q,%v want %q", id, got, ok, typ)
		}
	}
	// non members never panic and return a known type or nothing
	known := map[string]bool{}
	for _, n := range names {
		known[n] = true
	}
	for _, id := range testIDs(rand.New(rand.NewPCG(99, 99)), 10000) {
		if typ, ok := tab.Lookup(id); ok && !known[typ] {
			t.Fatalf("unknown type %q", typ)
		}
	}
	tab.Lookup("")
	tab.Lookup("x")
}

func TestRoundtrip30Types(t *testing.T)  { testRoundtrip(t, 1_000_000, 30) }
func TestRoundtrip250Types(t *testing.T) { testRoundtrip(t, 1_000_000, 250) }
func TestRoundtripSmall(t *testing.T) {
	for _, n := range []int{0, 1, 2, 3, 63, 64, 65, 1000} {
		for _, types := range []int{1, 2, 3, 5} {
			testRoundtrip(t, n, types)
		}
	}
}

func TestSingleTypeValueWidth(t *testing.T) {
	b := newTestBuilder(t, BuildOptions{})
	_ = b.Add("02IiNr000000000000000000000000000000A", "A")
	path, st := buildTo(t, b)
	if st.ValueWidth != 1 {
		t.Fatalf("width %d", st.ValueWidth)
	}
	tab, _ := Open(path, OpenOptions{})
	defer tab.Close()
	if typ, ok := tab.Lookup("02IiNr000000000000000000000000000000A"); !ok || typ != "A" {
		t.Fatal(typ, ok)
	}
}

func TestDuplicates(t *testing.T) {
	b := newTestBuilder(t, BuildOptions{})
	ids := testIDs(rand.New(rand.NewPCG(3, 3)), 1000)
	for _, id := range ids {
		_ = b.Add(id, "A")
		_ = b.Add(id, "A") // same type: ignored
	}
	path, st := buildTo(t, b)
	if st.KeyCount != 1000 || st.InputCount != 2000 {
		t.Fatalf("%+v", st)
	}
	tab, _ := Open(path, OpenOptions{})
	defer tab.Close()
	for _, id := range ids {
		if typ, ok := tab.Lookup(id); !ok || typ != "A" {
			t.Fatalf("%s: %q %v", id, typ, ok)
		}
	}
}

func TestConflictingDuplicatesFail(t *testing.T) {
	csv := "02IiNr000000000000000000000000000000A;A\n02IiNr000000000000000000000000000000B;B\n02IiNr000000000000000000000000000000A;B\n"
	b := newTestBuilder(t, BuildOptions{})
	if _, err := b.ReadCSV(strings.NewReader(csv)); err != nil {
		t.Fatal(err)
	}
	_, err := b.Build(&bytes.Buffer{})
	var ce *ConflictError
	if !errors.As(err, &ce) || len(ce.Hashes) != 1 {
		t.Fatalf("expected one conflict, got %v", err)
	}
	ids, _ := ce.FindIDs(strings.NewReader(csv), 10)
	if len(ids) != 2 || !strings.HasSuffix(ids[0], ";A") || !strings.HasSuffix(ids[1], ";B") {
		t.Fatalf("%v", ids)
	}
}

func TestFallbackTable(t *testing.T) {
	levelPosHook = func(level uint32, size uint64) uint64 { return 5 % size }
	defer func() { levelPosHook = nil }()
	rng := rand.New(rand.NewPCG(5, 5))
	ids := testIDs(rng, 5000)
	b := newTestBuilder(t, BuildOptions{MaxLevels: 4})
	want := map[string]string{}
	for i, id := range ids {
		typ := fmt.Sprintf("T%d", i%7)
		want[id] = typ
		_ = b.Add(id, typ)
	}
	path, st := buildTo(t, b)
	if st.FallbackCount != 5000 {
		t.Fatalf("expected everything in fallback, got %d", st.FallbackCount)
	}
	if _, err := VerifyFile(path); err != nil {
		t.Fatal(err)
	}
	tab, _ := Open(path, OpenOptions{})
	defer tab.Close()
	for id, typ := range want {
		if got, ok := tab.Lookup(id); !ok || got != typ {
			t.Fatalf("%s: %q %v want %q", id, got, ok, typ)
		}
	}
	// a non member is not found in the fallback table, and no level bit is set for it
	if typ, ok := tab.Lookup("02IiNr00000000000000000000000notAMember"); ok {
		t.Fatalf("non member got %q", typ)
	}
}

func TestFallbackMixedWithLevels(t *testing.T) {
	// only a few levels with gamma 1: many keys fall through to the fallback table
	rng := rand.New(rand.NewPCG(8, 8))
	ids := testIDs(rng, 20000)
	b := newTestBuilder(t, BuildOptions{MaxLevels: 2, Gamma: 1})
	want := map[string]string{}
	for i, id := range ids {
		typ := fmt.Sprintf("T%d", i%11)
		want[id] = typ
		_ = b.Add(id, typ)
	}
	path, st := buildTo(t, b)
	if st.FallbackCount == 0 || st.FallbackCount == 20000 {
		t.Fatalf("fallback %d", st.FallbackCount)
	}
	tab, _ := Open(path, OpenOptions{})
	defer tab.Close()
	for id, typ := range want {
		if got, ok := tab.Lookup(id); !ok || got != typ {
			t.Fatalf("%s: %q %v want %q", id, got, ok, typ)
		}
	}
}

func TestDeterministicWithFixedKey(t *testing.T) {
	key := [16]byte{9, 8, 7}
	build := func(order []int, ids []string) []byte {
		b := newTestBuilder(t, BuildOptions{HashKey: &key, CreatedAtMs: 1})
		for _, i := range order {
			_ = b.Add(ids[i], fmt.Sprintf("T%d", i%5))
		}
		var buf bytes.Buffer
		if _, err := b.Build(&buf); err != nil {
			t.Fatal(err)
		}
		return buf.Bytes()
	}
	ids := testIDs(rand.New(rand.NewPCG(4, 4)), 500)
	order := make([]int, 500)
	for i := range order {
		order[i] = i
	}
	a := build(order, ids)
	b := build(order, ids)
	if !bytes.Equal(a, b) {
		t.Fatal("same input and key must give identical files")
	}
}

func TestCSVValidation(t *testing.T) {
	good := "02IiNr000000000000000000000000000000A"
	cases := map[string]string{
		"missing sep":    good + "\n",
		"empty type":     good + ";\n",
		"two seps":       good + ";A;B\n",
		"short id":       "abc;A\n",
		"bad id char":    "02IiNr00000000000000000000000000000-A;A\n",
		"empty line":     "\n",
		"long type":      good + ";" + strings.Repeat("x", 256) + "\n",
		"control char":   good + ";A\rB\n",
		"empty id":       ";A\n",
		"id with spaces": "02IiNr0000000000000000000000000000 0A;A\n",
	}
	for name, csv := range cases {
		b := newTestBuilder(t, BuildOptions{})
		if _, err := b.ReadCSV(strings.NewReader(csv)); err == nil {
			t.Errorf("%s: expected error", name)
		}
	}
	b := newTestBuilder(t, BuildOptions{})
	n, err := b.ReadCSV(strings.NewReader(good + ";Ünï:Type\n02IiNr000000000000000000000000000000B;B")) // no trailing newline
	if err != nil || n != 2 {
		t.Fatalf("%d %v", n, err)
	}
}

func TestTooManyTypes(t *testing.T) {
	b := newTestBuilder(t, BuildOptions{})
	for i := 0; i < MaxTypes; i++ {
		if err := b.Add("02IiNr000000000000000000000000000000A", fmt.Sprintf("t%d", i)); err != nil {
			t.Fatal(err)
		}
	}
	if err := b.Add("02IiNr000000000000000000000000000000A", "one-too-many"); err == nil {
		t.Fatal("expected error")
	}
}

func TestFilePreface(t *testing.T) {
	b := newTestBuilder(t, BuildOptions{Generator: "Ember Nexus API 1.2.3", CreatedAtMs: 1795371240000})
	for i, id := range testIDs(rand.New(rand.NewPCG(5, 5)), 500) {
		_ = b.Add(id, fmt.Sprintf("T%d", i%3))
	}
	var buf bytes.Buffer
	st, err := b.Build(&buf)
	if err != nil {
		t.Fatal(err)
	}
	file := buf.Bytes()
	if uint64(len(file)) != st.Bytes {
		t.Fatalf("stats say %d bytes, file has %d", st.Bytes, len(file))
	}
	pre, err := parsePreamble(file)
	if err != nil {
		t.Fatal(err)
	}
	if pre.contentOffset%sectionAlign != 0 {
		t.Errorf("content starts at %d, not 64 byte aligned", pre.contentOffset)
	}
	head := string(file[:pre.contentOffset])
	for _, want := range []string{
		"MPHF\ngenerated by: Ember Nexus API 1.2.3\ngenerated on: 2026-11-22 18:14\ncontent length: ",
		"\nsha256: ",
	} {
		if !strings.Contains(head, want) {
			t.Errorf("preface %q lacks %q", head, want)
		}
	}
	if !strings.HasSuffix(head, "\n\n") || strings.Contains(head[:len(head)-2], "\n\n") {
		t.Errorf("preface does not end at the first blank line: %q", head)
	}
	sum := sha256.Sum256(file[pre.contentOffset:])
	if !strings.Contains(head, "sha256: "+hex.EncodeToString(sum[:])) {
		t.Error("preface hash is not the SHA-256 of the content")
	}
	if pre.contentLength != uint64(len(file)-pre.contentOffset) {
		t.Error("content length does not match")
	}
	if _, err := NewBuilder(BuildOptions{Generator: "a\nb"}); err == nil {
		t.Error("generator with a newline accepted")
	}
}

func TestCorruptFilesAreRejected(t *testing.T) {
	b := newTestBuilder(t, BuildOptions{})
	for i, id := range testIDs(rand.New(rand.NewPCG(6, 6)), 2000) {
		_ = b.Add(id, fmt.Sprintf("T%d", i%9))
	}
	var buf bytes.Buffer
	if _, err := b.Build(&buf); err != nil {
		t.Fatal(err)
	}
	orig := buf.Bytes()
	pre, err := parsePreamble(orig)
	if err != nil {
		t.Fatal(err)
	}
	dir := t.TempDir()
	try := func(name string, data []byte) error {
		p := filepath.Join(dir, name)
		if err := os.WriteFile(p, data, 0o644); err != nil {
			t.Fatal(err)
		}
		_, err := VerifyFile(p)
		return err
	}
	if err := try("ok", orig); err != nil {
		t.Fatal(err)
	}
	// every truncation is rejected, no matter where
	for _, cut := range []int{0, 1, 4, 5, 20, pre.contentOffset - 1, pre.contentOffset, pre.contentOffset + 40, pre.contentOffset + HeaderSize, len(orig) - 1, len(orig) - 33, len(orig) / 2} {
		if err := try("trunc", orig[:cut]); err == nil {
			t.Errorf("truncated to %d bytes accepted", cut)
		} else if !errors.Is(err, ErrFormat) && cut >= 5 {
			t.Errorf("truncated to %d bytes: error does not wrap ErrFormat: %v", cut, err)
		}
	}
	if err := try("extra", append(append([]byte(nil), orig...), 0)); err == nil {
		t.Error("file with trailing byte accepted")
	}
	// flipping a byte of the content is caught by the hash; flipping the length or the hash in the preface as well
	for pos := pre.contentOffset; pos < len(orig); pos += 97 {
		c := append([]byte(nil), orig...)
		c[pos] ^= 0x55
		if err := try("flip", c); err == nil {
			t.Errorf("flipped byte %d accepted", pos)
		}
	}
	head := string(orig[:pre.contentOffset])
	for _, key := range []string{"content length: ", "sha256: "} {
		pos := strings.Index(head, key) + len(key)
		c := append([]byte(nil), orig...)
		if c[pos] == '1' {
			c[pos] = '2'
		} else {
			c[pos] = '1'
		}
		if err := try("preface", c); err == nil {
			t.Errorf("changed %q accepted", key)
		}
	}
	// a file without the blank line, with a wrong magic or without the required lines
	for name, data := range map[string][]byte{
		"magic":        append([]byte("XPHF"), orig[4:]...),
		"no blank":     bytes.ReplaceAll(orig[:pre.contentOffset], []byte("\n\n"), []byte("\n")),
		"no hash line": bytes.Replace(orig, []byte("sha256: "), []byte("sha257: "), 1),
	} {
		if err := try(name, data); err == nil {
			t.Errorf("%s accepted", name)
		}
	}
	// structural checks without the hash (the hash would reject all of these first)
	for _, mut := range []struct {
		name string
		fn   func(content []byte)
	}{
		{"content magic", func(b []byte) { b[0] = 'X' }},
		{"version", func(b []byte) { b[offVersion] = 9 }},
		{"algorithm", func(b []byte) { b[offAlgorithm] = 9 }},
		{"width", func(b []byte) { b[offValueWidth] = 0 }},
		{"levels", func(b []byte) { b[offLevelCount+1] = 0xff }},
		{"section", func(b []byte) { b[offSections+16*secBits] += 64 }},
	} {
		c := append([]byte(nil), orig...)
		mut.fn(c[pre.contentOffset:])
		if _, err := parseFile(c, false); err == nil {
			t.Errorf("mutation %s accepted", mut.name)
		} else if !errors.Is(err, ErrFormat) {
			t.Errorf("mutation %s: error does not wrap ErrFormat: %v", mut.name, err)
		}
		if err := try("mut", c); err == nil {
			t.Errorf("mutation %s accepted by VerifyFile", mut.name)
		}
	}
	// the hash check can be skipped on request, the length check can not
	p := filepath.Join(dir, "skip")
	c := append([]byte(nil), orig...)
	c[len(c)-1] ^= 0x01 // inside the (padded) fallback section: structurally fine, hash wrong
	if err := os.WriteFile(p, c, 0o644); err != nil {
		t.Fatal(err)
	}
	if _, err := Open(p, OpenOptions{}); err == nil {
		t.Error("changed content accepted by Open")
	}
	tab, err := Open(p, OpenOptions{SkipHashCheck: true})
	if err != nil {
		t.Fatalf("SkipHashCheck: %v", err)
	}
	_ = tab.Close()
	if err := os.WriteFile(p, orig[:len(orig)-1], 0o644); err != nil {
		t.Fatal(err)
	}
	if _, err := Open(p, OpenOptions{SkipHashCheck: true}); err == nil {
		t.Error("truncated file accepted with SkipHashCheck")
	}
}

func FuzzParseTable(f *testing.F) {
	b, _ := NewBuilder(BuildOptions{HashKey: &[16]byte{1}, CreatedAtMs: 1})
	for i, id := range testIDs(rand.New(rand.NewPCG(7, 7)), 300) {
		_ = b.Add(id, fmt.Sprintf("T%d", i%4))
	}
	var buf bytes.Buffer
	if _, err := b.Build(&buf); err != nil {
		f.Fatal(err)
	}
	f.Add(buf.Bytes())
	f.Add(buf.Bytes()[:HeaderSize])
	f.Fuzz(func(t *testing.T, data []byte) {
		tab, err := parseFile(data, false)
		if err != nil {
			return
		}
		// accepted files must be safe to query
		tab.Lookup("02IiNr000000000000000000000000000000A")
		tab.Lookup("")
		_ = tab.Info()
	})
}

func TestIsDataConflict(t *testing.T) {
	for name, tc := range map[string]struct {
		lines []string
		want  bool
	}{
		"same id, different types": {[]string{"a;A", "b;B", "a;B"}, true},
		"different ids":            {[]string{"a;A", "b;B"}, false},
		"same id, same type":       {[]string{"a;A", "a;A"}, false},
		"nothing found":            {nil, false},
	} {
		if got := IsDataConflict(tc.lines); got != tc.want {
			t.Errorf("%s: got %v", name, got)
		}
	}
}

func resolveCSV(t *testing.T, csv string, attempts int, onRetry func(int)) (*Stats, error) {
	t.Helper()
	open := func() (io.ReadCloser, error) { return io.NopCloser(strings.NewReader(csv)), nil }
	st, _, err := BuildFileResolvingConflicts(BuildOptions{}, open, filepath.Join(t.TempDir(), "r.mphf"), attempts, onRetry)
	return st, err
}

func TestResolvingConflictsReportsDataConflicts(t *testing.T) {
	csv := "02IiNr000000000000000000000000000000A;A\n02IiNr000000000000000000000000000000A;B\n02IiNr000000000000000000000000000000B;A\n"
	retries := 0
	_, err := resolveCSV(t, csv, 5, func(int) { retries++ })
	var dce *DataConflictError
	if !errors.As(err, &dce) || retries != 0 || dce.Attempts != 1 || len(dce.Lines) != 2 {
		t.Fatalf("err %v, retries %d", err, retries)
	}
	var ce *ConflictError
	if !errors.As(err, &ce) {
		t.Fatal("DataConflictError does not unwrap to ConflictError")
	}
}

func TestResolvingConflictsRebuildsAfterHashCollision(t *testing.T) {
	// two different ids which collide in the first build only; the key hook stands in for a hash collision
	idA, idB := "02IiNr000000000000000000000000000000A", "02IiNr000000000000000000000000000000B"
	csv := idA + ";A\n" + idB + ";B\n"
	keyHook = func(string) string { return idA }
	defer func() { keyHook = nil }()
	retries := 0
	st, err := resolveCSV(t, csv, 5, func(int) { retries++; keyHook = nil })
	if err != nil {
		t.Fatal(err)
	}
	if retries != 1 || st.KeyCount != 2 {
		t.Fatalf("retries %d, stats %+v", retries, st)
	}
}

func TestResolvingConflictsGivesUp(t *testing.T) {
	idA, idB := "02IiNr000000000000000000000000000000A", "02IiNr000000000000000000000000000000B"
	keyHook = func(string) string { return idA }
	defer func() { keyHook = nil }()
	retries := 0
	_, err := resolveCSV(t, idA+";A\n"+idB+";B\n", 3, func(int) { retries++ })
	var dce *DataConflictError
	if !errors.As(err, &dce) || retries != 2 || dce.Attempts != 3 {
		t.Fatalf("err %v, retries %d", err, retries)
	}
}

// The alignment rules of the preface are asserted by the writer and enforced by readers.
func TestPrefaceAlignmentRules(t *testing.T) {
	var sum [sha256.Size]byte
	for genLen := 0; genLen <= 130; genLen++ {
		for _, contentLength := range []uint64{0, 9, 10, 99_999, 1 << 40} {
			generator := strings.Repeat("g", genLen)
			pre, err := encodePreamble(generator, 1795371240000, contentLength, sum)
			if err != nil {
				t.Fatal(err)
			}
			if len(pre)%sectionAlign != 0 {
				t.Fatalf("generator %d, length %d: preface has %d bytes", genLen, contentLength, len(pre))
			}
			parsed, err := parsePreamble(append(append([]byte(nil), pre...), 'x'))
			if err != nil || parsed.contentOffset != len(pre) || parsed.generator != generator || parsed.contentLength != contentLength {
				t.Fatalf("generator %d, length %d: %v %+v", genLen, contentLength, err, parsed)
			}
			padding := len(pre) - len(strings.TrimRight(string(pre[:len(pre)-2]), " ")) - 2
			if padding < 0 || padding >= sectionAlign {
				t.Fatalf("padding of %d bytes", padding)
			}
		}
	}
	if _, err := NewBuilder(BuildOptions{Generator: "ends with space "}); err == nil {
		t.Error("generator with a trailing space accepted")
	}
}

func TestReadersRejectBrokenPadding(t *testing.T) {
	var sum [sha256.Size]byte
	good, err := encodePreamble("Ember Nexus API 1", 1795371240000, 5, sum)
	if err != nil {
		t.Fatal(err)
	}
	parse := func(preface string) error {
		_, err := parsePreamble([]byte(preface + "content"))
		return err
	}
	if err := parse(string(good)); err != nil {
		t.Fatal(err)
	}
	base := strings.TrimRight(string(good[:len(good)-2]), " ") // up to the end of the hash
	for name, preface := range map[string]string{
		"content not aligned":       base + "\n\n",
		"aligned, 64 spaces":        base + strings.Repeat(" ", 64+(sectionAlign-(len(base)+2)%sectionAlign)%sectionAlign) + "\n\n",
		"padding on another line":   strings.Replace(string(good), "generated by: Ember Nexus API 1", "generated by: Ember Nexus API 1 ", 1),
		"padding in front of hash":  strings.Replace(string(good), "sha256: ", "sha256:  ", 1),
		"padding is not only space": strings.Replace(string(good), "  \n\n", " x\n\n", 1),
	} {
		if strings.Contains(name, "only space") && preface == string(good) {
			t.Fatal("test input did not change") // the good preface has less than two padding bytes by chance
		}
		if err := parse(preface); err == nil {
			t.Errorf("%s accepted", name)
		} else if !errors.Is(err, ErrFormat) {
			t.Errorf("%s: error does not wrap ErrFormat: %v", name, err)
		}
	}
}

func TestBuiltFilesAreReadOnly(t *testing.T) {
	b := newTestBuilder(t, BuildOptions{})
	_ = b.Add("02IiNr000000000000000000000000000000A", "A")
	path, _ := buildTo(t, b)
	st, err := os.Stat(path)
	if err != nil {
		t.Fatal(err)
	}
	if st.Mode().Perm()&0o222 != 0 {
		t.Errorf("built file is writable: %v", st.Mode())
	}
	// a leftover temporary file of a crashed build (also read-only) does not stop the next build
	b2 := newTestBuilder(t, BuildOptions{})
	_ = b2.Add("02IiNr000000000000000000000000000000A", "A")
	if err := os.WriteFile(path+".tmp", []byte("left over"), 0o444); err != nil {
		t.Fatal(err)
	}
	if _, err := b2.BuildFile(path); err != nil {
		t.Fatal(err)
	}
}

func TestFindIDsSkipsOversizedLines(t *testing.T) {
	b, err := NewBuilder(BuildOptions{})
	if err != nil {
		t.Fatal(err)
	}
	h0, h1 := b.key.sum128("target")
	ce := &ConflictError{Hashes: [][2]uint64{{h0, h1}}, hasher: b.key}
	input := strings.Repeat("x", 3*maxConflictLineSize) + "\n" + "target;n:A\n" + "other;n:B\n" + "target;n:B"
	got, err := ce.FindIDs(strings.NewReader(input), 20)
	if err != nil {
		t.Fatal(err)
	}
	if len(got) != 2 || got[0] != "target;n:A" || got[1] != "target;n:B" {
		t.Fatalf("got %v", got)
	}
}
