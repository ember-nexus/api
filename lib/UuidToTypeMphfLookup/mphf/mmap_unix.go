//go:build unix

package mphf

import (
	"fmt"
	"os"
	"syscall"
)

func mapFile(path string) ([]byte, error) {
	f, err := os.Open(path)
	if err != nil {
		return nil, err
	}
	defer f.Close()
	st, err := f.Stat()
	if err != nil {
		return nil, err
	}
	if !st.Mode().IsRegular() {
		return nil, fmt.Errorf("%s is not a regular file", path)
	}
	size := st.Size()
	if size < int64(len(preambleMagic))+HeaderSize {
		return nil, formatErr("file too small (%d bytes)", size)
	}
	if int64(int(size)) != size {
		return nil, fmt.Errorf("file too large to map")
	}
	return syscall.Mmap(int(f.Fd()), 0, int(size), syscall.PROT_READ, syscall.MAP_SHARED)
}

func unmap(data []byte) error { return syscall.Munmap(data) }

// adviseRandom marks a page aligned range inside b as randomly accessed (MADV_RANDOM).
func adviseRandom(b []byte, offset, length int) {
	page := os.Getpagesize()
	start := (offset + page - 1) / page * page
	end := (offset + length) / page * page
	if end <= start {
		return
	}
	_ = syscall.Madvise(b[start:end], syscall.MADV_RANDOM)
}
