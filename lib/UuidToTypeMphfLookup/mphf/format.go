// Package mphf implements the `.mphf` file: a static, keyless id -> type lookup structure (BBHash minimal perfect
// hash function with SipHash-2-4-128, a packed value array and a small fallback table), plus the builder that creates
// it and a memory mapped reader.
//
// A file starts with a short, human readable preface (see preamble.go) followed by the binary content, whose format
// (version 1) is documented in this module's README.
package mphf

import (
	"encoding/binary"
	"errors"
	"fmt"
	"math/bits"
)

const (
	// HeaderSize is the fixed size of the header in bytes.
	HeaderSize = 256
	// FormatVersion is the only supported file format version.
	FormatVersion = 1
	// AlgorithmBBHashSipHash24x128 identifies BBHash + SipHash-2-4 (128 bit output).
	AlgorithmBBHashSipHash24x128 = 1

	// IDLength is the length of an element id in its canonical text form (base62sort).
	IDLength = 37
	// MaxTypeNameLength is the maximum length of a type name in bytes.
	MaxTypeNameLength = 255
	// MaxTypes is the maximum number of distinct types (type indices are 16 bit).
	MaxTypes = 65535

	// DefaultGamma is the default BBHash space factor.
	DefaultGamma = 2.0
	// DefaultMaxLevels is the default maximum number of BBHash levels.
	DefaultMaxLevels = 32

	sectionAlign  = 64
	rankBlockBits = 512
	rankBlockWord = rankBlockBits / 64
	fallbackRec   = 18
	levelRec      = 16
	maxLevelCount = 1024
)

var magic = [8]byte{'E', 'N', 'M', 'P', 'H', 'F', 0, 1}

// Section indices, in file order.
const (
	secDict = iota
	secLevels
	secBits
	secRank
	secValues
	secFallback
	secCount
)

type section struct {
	offset, length uint64
}

type header struct {
	formatVersion uint16
	algorithm     uint16
	flags         uint32
	createdAtMs   uint64
	watermarkMs   uint64
	keyCount      uint64 // unique keys: placedCount + fallbackCount
	hashKey       [16]byte
	gammaMilli    uint32
	levelCount    uint32
	valueWidth    uint8
	placedCount   uint64
	fallbackCount uint64
	sections      [secCount]section
}

// Header field offsets.
const (
	offVersion       = 8
	offAlgorithm     = 10
	offFlags         = 12
	offCreatedAt     = 16
	offWatermark     = 24
	offKeyCount      = 32
	offHashKey       = 40
	offGamma         = 56
	offLevelCount    = 60
	offValueWidth    = 64
	offPlacedCount   = 72
	offFallbackCount = 80
	offSections      = 96
)

func (h *header) encode() []byte {
	b := make([]byte, HeaderSize)
	copy(b[0:8], magic[:])
	binary.LittleEndian.PutUint16(b[offVersion:], h.formatVersion)
	binary.LittleEndian.PutUint16(b[offAlgorithm:], h.algorithm)
	binary.LittleEndian.PutUint32(b[offFlags:], h.flags)
	binary.LittleEndian.PutUint64(b[offCreatedAt:], h.createdAtMs)
	binary.LittleEndian.PutUint64(b[offWatermark:], h.watermarkMs)
	binary.LittleEndian.PutUint64(b[offKeyCount:], h.keyCount)
	copy(b[offHashKey:], h.hashKey[:])
	binary.LittleEndian.PutUint32(b[offGamma:], h.gammaMilli)
	binary.LittleEndian.PutUint32(b[offLevelCount:], h.levelCount)
	b[offValueWidth] = h.valueWidth
	binary.LittleEndian.PutUint64(b[offPlacedCount:], h.placedCount)
	binary.LittleEndian.PutUint64(b[offFallbackCount:], h.fallbackCount)
	for i, s := range h.sections {
		binary.LittleEndian.PutUint64(b[offSections+16*i:], s.offset)
		binary.LittleEndian.PutUint64(b[offSections+16*i+8:], s.length)
	}
	return b
}

var (
	// ErrFormat is wrapped by every error that says a file is not a valid `.mphf` file.
	ErrFormat = errors.New("invalid mphf file")
)

func formatErr(format string, args ...any) error {
	return fmt.Errorf("%w: %s", ErrFormat, fmt.Sprintf(format, args...))
}

func decodeHeader(b []byte) (*header, error) {
	if len(b) < HeaderSize {
		return nil, formatErr("file shorter than the header (%d bytes)", len(b))
	}
	if [8]byte(b[0:8]) != magic {
		return nil, formatErr("bad magic")
	}
	h := &header{
		formatVersion: binary.LittleEndian.Uint16(b[offVersion:]),
		algorithm:     binary.LittleEndian.Uint16(b[offAlgorithm:]),
		flags:         binary.LittleEndian.Uint32(b[offFlags:]),
		createdAtMs:   binary.LittleEndian.Uint64(b[offCreatedAt:]),
		watermarkMs:   binary.LittleEndian.Uint64(b[offWatermark:]),
		keyCount:      binary.LittleEndian.Uint64(b[offKeyCount:]),
		gammaMilli:    binary.LittleEndian.Uint32(b[offGamma:]),
		levelCount:    binary.LittleEndian.Uint32(b[offLevelCount:]),
		valueWidth:    b[offValueWidth],
		placedCount:   binary.LittleEndian.Uint64(b[offPlacedCount:]),
		fallbackCount: binary.LittleEndian.Uint64(b[offFallbackCount:]),
	}
	copy(h.hashKey[:], b[offHashKey:offHashKey+16])
	for i := range h.sections {
		h.sections[i].offset = binary.LittleEndian.Uint64(b[offSections+16*i:])
		h.sections[i].length = binary.LittleEndian.Uint64(b[offSections+16*i+8:])
	}
	if h.formatVersion != FormatVersion {
		return nil, formatErr("unsupported format version %d", h.formatVersion)
	}
	if h.algorithm != AlgorithmBBHashSipHash24x128 {
		return nil, formatErr("unsupported algorithm %d", h.algorithm)
	}
	return h, nil
}

func alignUp(v, a uint64) uint64 { return (v + a - 1) &^ (a - 1) }

// valueWidthFor returns the number of bits per packed value for the given number of types.
func valueWidthFor(typeCount int) uint8 {
	if typeCount <= 2 {
		return 1
	}
	return uint8(bits.Len(uint(typeCount - 1)))
}

// layout computes the canonical section offsets (relative to the start of the content) for the given section
// lengths, plus the total content size. Every section starts on a 64 byte boundary, the last one is padded.
func layout(lengths [secCount]uint64) (sections [secCount]section, total uint64) {
	off := uint64(HeaderSize)
	for i, l := range lengths {
		sections[i] = section{offset: off, length: l}
		off = alignUp(off+l, sectionAlign)
	}
	return sections, off
}

func ceilDiv(a, b uint64) uint64 { return (a + b - 1) / b }
