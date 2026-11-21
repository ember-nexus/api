package mphf

import (
	"bufio"
	"bytes"
	"errors"
	"fmt"
	"io"
	"unsafe"
)

var idChar = func() (t [256]bool) {
	for c := '0'; c <= '9'; c++ {
		t[c] = true
	}
	for c := 'A'; c <= 'Z'; c++ {
		t[c] = true
	}
	for c := 'a'; c <= 'z'; c++ {
		t[c] = true
	}
	return
}()

// ValidateID checks that id is exactly 37 characters from [0-9A-Za-z] (canonical base62sort form).
func ValidateID(id string) error {
	if len(id) != IDLength {
		return fmt.Errorf("id %q has length %d, expected %d", truncate(id), len(id), IDLength)
	}
	for i := 0; i < len(id); i++ {
		if !idChar[id[i]] {
			return fmt.Errorf("id %q contains invalid character at position %d", truncate(id), i)
		}
	}
	return nil
}

// ValidateTypeName checks the type name rules of the CSV format: 1..255 bytes, no `;`, no control characters.
func ValidateTypeName(name string) error {
	if len(name) == 0 {
		return fmt.Errorf("type name is empty")
	}
	if len(name) > MaxTypeNameLength {
		return fmt.Errorf("type name %q... is longer than %d bytes", truncate(name), MaxTypeNameLength)
	}
	for i := 0; i < len(name); i++ {
		if name[i] == ';' {
			return fmt.Errorf("type name %q contains ';'", name)
		}
		if name[i] < 0x20 {
			return fmt.Errorf("type name %q contains a control character", name)
		}
	}
	return nil
}

func truncate(s string) string {
	if len(s) > 64 {
		return s[:64] + "..."
	}
	return s
}

// bytesToString converts without copying; the result must not outlive or mutate b.
func bytesToString(b []byte) string {
	if len(b) == 0 {
		return ""
	}
	return unsafe.String(&b[0], len(b))
}

// ReadCSV streams `<id>;<type>\n` lines from r into the builder. It returns the number of lines read. Lines must
// have exactly one `;`, a valid id and a valid type name; the last line may omit the trailing newline.
func (b *Builder) ReadCSV(r io.Reader) (int64, error) {
	br := bufio.NewReaderSize(r, 1<<20)
	var lineNo int64
	for {
		line, err := br.ReadSlice('\n')
		if errors.Is(err, bufio.ErrBufferFull) {
			return lineNo, fmt.Errorf("line %d: line too long", lineNo+1)
		}
		if err != nil && !errors.Is(err, io.EOF) {
			return lineNo, err
		}
		if len(line) == 0 && errors.Is(err, io.EOF) {
			return lineNo, nil
		}
		lineNo++
		if n := len(line); n > 0 && line[n-1] == '\n' {
			line = line[:n-1]
		}
		sep := bytes.IndexByte(line, ';')
		if sep < 0 {
			return lineNo, fmt.Errorf("line %d: missing ';' separator", lineNo)
		}
		idB, typB := line[:sep], line[sep+1:]
		if bytes.IndexByte(typB, ';') >= 0 {
			return lineNo, fmt.Errorf("line %d: more than one ';'", lineNo)
		}
		id := bytesToString(idB)
		if verr := ValidateID(id); verr != nil {
			return lineNo, fmt.Errorf("line %d: %w", lineNo, verr)
		}
		if aerr := b.addBytes(id, typB); aerr != nil {
			return lineNo, fmt.Errorf("line %d: %w", lineNo, aerr)
		}
		if errors.Is(err, io.EOF) {
			return lineNo, nil
		}
	}
}
