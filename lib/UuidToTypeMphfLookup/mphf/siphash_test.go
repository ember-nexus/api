package mphf

import (
	"encoding/binary"
	"encoding/hex"
	"testing"
)

func TestSipHash128ReferenceVectors(t *testing.T) {
	var key [16]byte
	for i := range key {
		key[i] = byte(i)
	}
	k := newHashKey(key)
	input := make([]byte, 0, 64)
	for i := 0; i < 64; i++ {
		h0, h1 := k.sum128(string(input))
		var got [16]byte
		binary.LittleEndian.PutUint64(got[0:8], h0)
		binary.LittleEndian.PutUint64(got[8:16], h1)
		if hex.EncodeToString(got[:]) != sip128Vectors[i] {
			t.Fatalf("len %d: got %x want %s", i, got, sip128Vectors[i])
		}
		input = append(input, byte(i))
	}
}

func TestSumDoesNotAllocate(t *testing.T) {
	k := newHashKey([16]byte{1, 2, 3})
	s := "02IiNr0000000000000000000000000000000"
	if n := testing.AllocsPerRun(100, func() { k.sum128(s) }); n != 0 {
		t.Fatalf("allocs: %v", n)
	}
}
