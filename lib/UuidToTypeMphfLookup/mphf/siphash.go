package mphf

import "math/bits"

// hashKey is the per-build secret key of the SipHash-2-4 (128 bit output) function used to hash every id.
type hashKey struct {
	k0, k1 uint64
}

func newHashKey(key [16]byte) hashKey {
	return hashKey{k0: le64(key[0:8]), k1: le64(key[8:16])}
}

func le64(b []byte) uint64 {
	_ = b[7]
	return uint64(b[0]) | uint64(b[1])<<8 | uint64(b[2])<<16 | uint64(b[3])<<24 |
		uint64(b[4])<<32 | uint64(b[5])<<40 | uint64(b[6])<<48 | uint64(b[7])<<56
}

func le64s(s string, i int) uint64 {
	_ = s[i+7]
	return uint64(s[i]) | uint64(s[i+1])<<8 | uint64(s[i+2])<<16 | uint64(s[i+3])<<24 |
		uint64(s[i+4])<<32 | uint64(s[i+5])<<40 | uint64(s[i+6])<<48 | uint64(s[i+7])<<56
}

func sipRound(v0, v1, v2, v3 uint64) (uint64, uint64, uint64, uint64) {
	v0 += v1
	v1 = bits.RotateLeft64(v1, 13)
	v1 ^= v0
	v0 = bits.RotateLeft64(v0, 32)
	v2 += v3
	v3 = bits.RotateLeft64(v3, 16)
	v3 ^= v2
	v0 += v3
	v3 = bits.RotateLeft64(v3, 21)
	v3 ^= v0
	v2 += v1
	v1 = bits.RotateLeft64(v1, 17)
	v1 ^= v2
	v2 = bits.RotateLeft64(v2, 32)
	return v0, v1, v2, v3
}

// sum128 returns SipHash-2-4 with 128 bit output of data as two little endian words (first 8 and last 8 output
// bytes). It does not allocate.
func (k hashKey) sum128(data string) (uint64, uint64) {
	v0 := k.k0 ^ 0x736f6d6570736575
	v1 := k.k1 ^ 0x646f72616e646f6d ^ 0xee
	v2 := k.k0 ^ 0x6c7967656e657261
	v3 := k.k1 ^ 0x7465646279746573

	n := len(data)
	i := 0
	for ; i+8 <= n; i += 8 {
		m := le64s(data, i)
		v3 ^= m
		v0, v1, v2, v3 = sipRound(v0, v1, v2, v3)
		v0, v1, v2, v3 = sipRound(v0, v1, v2, v3)
		v0 ^= m
	}
	b := uint64(n) << 56
	for j := 0; i+j < n; j++ {
		b |= uint64(data[i+j]) << (8 * uint(j))
	}
	v3 ^= b
	v0, v1, v2, v3 = sipRound(v0, v1, v2, v3)
	v0, v1, v2, v3 = sipRound(v0, v1, v2, v3)
	v0 ^= b

	v2 ^= 0xee
	for r := 0; r < 4; r++ {
		v0, v1, v2, v3 = sipRound(v0, v1, v2, v3)
	}
	out0 := v0 ^ v1 ^ v2 ^ v3
	v1 ^= 0xdd
	for r := 0; r < 4; r++ {
		v0, v1, v2, v3 = sipRound(v0, v1, v2, v3)
	}
	out1 := v0 ^ v1 ^ v2 ^ v3
	return out0, out1
}

func fmix64(x uint64) uint64 {
	x ^= x >> 33
	x *= 0xff51afd7ed558ccd
	x ^= x >> 33
	x *= 0xc4ceb9fe1a85ec53
	x ^= x >> 33
	return x
}

// levelPosHook is a test-only hook: when set, it replaces the position computation of every level (used to force
// collisions and so exercise the fallback table). It must stay nil in production code.
var levelPosHook func(level uint32, size uint64) uint64

// levelPos derives the bit position of a key inside the given level (of `size` bits) from its 128 bit hash.
func levelPos(h0, h1 uint64, level uint32, size uint64) uint64 {
	if levelPosHook != nil {
		return levelPosHook(level, size)
	}
	x := fmix64(h0 ^ bits.RotateLeft64(h1, 29) ^ (uint64(level)+1)*0x9e3779b97f4a7c15)
	hi, _ := bits.Mul64(x, size)
	return hi
}
