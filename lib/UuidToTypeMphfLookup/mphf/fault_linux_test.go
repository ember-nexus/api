package mphf

import (
	"errors"
	"fmt"
	"math/rand/v2"
	"os"
	"testing"
)

// A file which shrinks while it is mapped makes every access to the lost pages a SIGBUS, which kills a Go program
// unless the goroutine asked for a panic instead (debug.SetPanicOnFault).
func TestSafeLookupSurvivesTruncatedMapping(t *testing.T) {
	b := newTestBuilder(t, BuildOptions{})
	ids := testIDs(rand.New(rand.NewPCG(8, 8)), 5000)
	for i, id := range ids {
		_ = b.Add(id, fmt.Sprintf("T%d", i%7))
	}
	path, _ := buildTo(t, b)
	tab, err := Open(path, OpenOptions{})
	if err != nil {
		t.Fatal(err)
	}
	defer tab.Close()

	for _, id := range ids[:100] {
		if _, ok, err := tab.SafeLookup(id); err != nil || !ok {
			t.Fatalf("lookup before truncation: %v %v", ok, err)
		}
	}
	// built files are read only, the test plays the part of an external process which is allowed to modify them
	if err := os.Chmod(path, 0o644); err != nil {
		t.Fatal(err)
	}
	if err := os.Truncate(path, 0); err != nil {
		t.Fatal(err)
	}
	faults := 0
	for _, id := range ids[:100] {
		_, _, err := tab.SafeLookup(id)
		if err != nil {
			if !errors.Is(err, ErrMappingFault) {
				t.Fatalf("unexpected error: %v", err)
			}
			faults++
		}
	}
	if faults != 100 {
		t.Fatalf("only %d of 100 lookups reported the fault", faults)
	}
	// the process is alive and other tables are not affected
	b2 := newTestBuilder(t, BuildOptions{})
	_ = b2.Add(ids[0], "X")
	path2, _ := buildTo(t, b2)
	tab2, err := Open(path2, OpenOptions{})
	if err != nil {
		t.Fatal(err)
	}
	defer tab2.Close()
	if typ, ok, err := tab2.SafeLookup(ids[0]); err != nil || !ok || typ != "X" {
		t.Fatalf("%q %v %v", typ, ok, err)
	}
}

func TestSafeLookupDoesNotSwallowOtherPanics(t *testing.T) {
	// a nil table is a programming error, not a fault; it must stay visible
	defer func() {
		if recover() == nil {
			t.Error("panic of a nil table was swallowed")
		}
	}()
	var tab *Table
	_, _, _ = tab.SafeLookup("x")
}
