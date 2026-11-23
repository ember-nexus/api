//go:build linux

package mphf

import (
	"fmt"
	"math/rand/v2"
	"os"
	"path/filepath"
	"runtime"
	"strings"
	"sync"
	"sync/atomic"
	"testing"
	"time"
)

func mappingsOf(path string) int {
	b, err := os.ReadFile("/proc/self/maps")
	if err != nil {
		return -1
	}
	n := 0
	for _, l := range strings.Split(string(b), "\n") {
		if strings.HasSuffix(l, path) || strings.Contains(l, path+" (deleted)") {
			n++
		}
	}
	return n
}

// Concurrent lookups while tables are swapped through an atomic pointer; old mappings must get unmapped.
func TestConcurrentLookupsDuringSwaps(t *testing.T) {
	rng := rand.New(rand.NewPCG(11, 11))
	ids := testIDs(rng, 20000)
	dir := t.TempDir()
	paths := make([]string, 2)
	for v := range paths {
		b := newTestBuilder(t, BuildOptions{})
		for _, id := range ids {
			_ = b.Add(id, fmt.Sprintf("V%d", v)) // the type tells which file answered
		}
		paths[v] = filepath.Join(dir, fmt.Sprintf("v%d.mphf", v))
		if _, err := b.BuildFile(paths[v]); err != nil {
			t.Fatal(err)
		}
	}

	var cur atomic.Pointer[Table]
	first, err := Open(paths[0], OpenOptions{})
	if err != nil {
		t.Fatal(err)
	}
	cur.Store(first)

	var stop atomic.Bool
	var wg sync.WaitGroup
	var bad atomic.Int64
	for g := 0; g < 8; g++ {
		wg.Add(1)
		go func() {
			defer wg.Done()
			r := rand.New(rand.NewPCG(uint64(g), 1))
			for !stop.Load() {
				id := ids[r.IntN(len(ids))]
				tab := cur.Load()
				if typ, ok := tab.Lookup(id); !ok || (typ != "V0" && typ != "V1") {
					bad.Add(1)
				}
			}
		}()
	}
	for i := 0; i < 60; i++ {
		tab, err := Open(paths[i%2], OpenOptions{})
		if err != nil {
			t.Fatal(err)
		}
		cur.Store(tab)
		if i%10 == 0 {
			runtime.GC()
		}
		time.Sleep(time.Millisecond)
	}
	stop.Store(true)
	wg.Wait()
	if bad.Load() != 0 {
		t.Fatalf("%d bad lookups", bad.Load())
	}
	// the cleanups run after a GC; give them a few cycles
	for i := 0; i < 20 && mappingsOf(dir) > 2; i++ {
		runtime.GC()
		time.Sleep(20 * time.Millisecond)
	}
	if n := mappingsOf(dir); n > 2 {
		t.Fatalf("%d mappings of the test files still exist, old tables are not unmapped", n)
	}
	runtime.KeepAlive(cur.Load())
}
