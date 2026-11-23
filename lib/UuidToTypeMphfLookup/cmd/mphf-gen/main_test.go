package main

import (
	"bytes"
	"strings"
	"testing"

	"github.com/ember-nexus/api/lib/UuidToTypeMphfLookup/mphf"
)

func generate(t *testing.T, args ...string) string {
	t.Helper()
	var out bytes.Buffer
	if err := run(args, &out); err != nil {
		t.Fatalf("run %v: %v", args, err)
	}
	return out.String()
}

func TestGeneratedRowsAreValidCSV(t *testing.T) {
	lines := strings.Split(strings.TrimSuffix(generate(t, "--count", "500", "--types", "7"), "\n"), "\n")
	if len(lines) != 500 {
		t.Fatalf("got %d lines, want 500", len(lines))
	}
	seen := map[string]bool{}
	for _, line := range lines {
		id, typ, ok := strings.Cut(line, ";")
		if !ok {
			t.Fatalf("line without separator: %q", line)
		}
		if err := mphf.ValidateID(id); err != nil {
			t.Fatal(err)
		}
		if err := mphf.ValidateTypeName(typ); err != nil {
			t.Fatal(err)
		}
		if !strings.HasPrefix(id, "02IiNr") {
			t.Fatalf("id %q has no sv2a7 prefix", id)
		}
		seen[typ] = true
	}
	if len(seen) > 7 {
		t.Fatalf("%d distinct types, want at most 7", len(seen))
	}
}

func TestIdsAreUnique(t *testing.T) {
	seen := map[string]bool{}
	for _, id := range strings.Split(strings.TrimSuffix(generate(t, "--count", "20000", "--ids-only"), "\n"), "\n") {
		if seen[id] {
			t.Fatalf("duplicate id %q", id)
		}
		seen[id] = true
	}
}

func TestIdsOnlyWritesNoTypes(t *testing.T) {
	for _, line := range strings.Split(strings.TrimSuffix(generate(t, "--count", "50", "--ids-only"), "\n"), "\n") {
		if err := mphf.ValidateID(line); err != nil {
			t.Fatalf("%q: %v", line, err)
		}
	}
}

func TestOutputIsDeterministicPerSeed(t *testing.T) {
	a := generate(t, "--count", "300", "--seed", "5")
	if a != generate(t, "--count", "300", "--seed", "5") {
		t.Error("same seed produced different output")
	}
	if a == generate(t, "--count", "300", "--seed", "6") {
		t.Error("different seeds produced the same output")
	}
}

func TestZeroCountWritesNothing(t *testing.T) {
	if out := generate(t, "--count", "0"); out != "" {
		t.Fatalf("got %q", out)
	}
}

// the generated data is the input of mphf-builder, so it has to build
func TestOutputBuilds(t *testing.T) {
	data := generate(t, "--count", "2000", "--types", "12")
	b, err := mphf.NewBuilder(mphf.BuildOptions{})
	if err != nil {
		t.Fatal(err)
	}
	n, err := b.ReadCSV(strings.NewReader(data))
	if err != nil {
		t.Fatal(err)
	}
	if n != 2000 {
		t.Fatalf("read %d rows, want 2000", n)
	}
	var img bytes.Buffer
	if _, err := b.Build(&img); err != nil {
		t.Fatal(err)
	}
}

func TestInvalidArguments(t *testing.T) {
	for _, args := range [][]string{{"--count", "-1"}, {"--types", "0"}, {"--unknown"}, {"--count", "x"}} {
		if err := run(args, &bytes.Buffer{}); err == nil {
			t.Errorf("run %v: expected an error", args)
		}
	}
}
