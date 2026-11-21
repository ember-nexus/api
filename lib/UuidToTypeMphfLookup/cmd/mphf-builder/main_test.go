package main

import (
	"fmt"
	"os"
	"path/filepath"
	"strings"
	"testing"

	"github.com/ember-nexus/api/lib/UuidToTypeMphfLookup/mphf"
)

func testID(i int) string { return fmt.Sprintf("%037d", i) }

func writeCSV(t *testing.T, dir string, lines ...string) string {
	t.Helper()
	p := filepath.Join(dir, "ids.csv")
	if err := os.WriteFile(p, []byte(strings.Join(lines, "\n")+"\n"), 0o600); err != nil {
		t.Fatal(err)
	}
	return p
}

func TestBuildVerifyLookup(t *testing.T) {
	dir := t.TempDir()
	csv := writeCSV(t, dir, testID(1)+";n:Data", testID(2)+";r:OWNS", testID(3)+";n:Data")
	out := filepath.Join(dir, "x.mphf")

	if err := runBuild([]string{"--input", csv, "--output", out}); err != nil {
		t.Fatalf("build: %v", err)
	}
	if err := runVerify([]string{out}); err != nil {
		t.Fatalf("verify: %v", err)
	}
	if err := runLookup([]string{out, testID(2)}); err != nil {
		t.Fatalf("lookup: %v", err)
	}
	tbl, err := mphf.Open(out, mphf.OpenOptions{})
	if err != nil {
		t.Fatal(err)
	}
	defer tbl.Close()
	if typ, ok := tbl.Lookup(testID(2)); !ok || typ != "r:OWNS" {
		t.Fatalf("lookup = %q, %v", typ, ok)
	}
}

func TestBuildRequiresInputAndOutput(t *testing.T) {
	if err := runBuild(nil); err == nil {
		t.Fatal("expected an error without --input/--output")
	}
}

func TestBuildRejectsInvalidLines(t *testing.T) {
	dir := t.TempDir()
	for name, line := range map[string]string{
		"semicolon in type": testID(1) + ";n:a;b",
		"short id":          "abc;n:Data",
		"no separator":      testID(1),
	} {
		t.Run(name, func(t *testing.T) {
			csv := writeCSV(t, dir, line)
			if err := runBuild([]string{"--input", csv, "--output", filepath.Join(dir, "bad.mphf")}); err == nil {
				t.Fatal("expected an error")
			}
		})
	}
}

func TestBuildReportsDataConflict(t *testing.T) {
	dir := t.TempDir()
	csv := writeCSV(t, dir, testID(1)+";n:A", testID(1)+";n:B")
	if err := runBuild([]string{"--input", csv, "--output", filepath.Join(dir, "c.mphf")}); err == nil {
		t.Fatal("expected a conflict error")
	}
	if _, err := os.Stat(filepath.Join(dir, "c.mphf")); err == nil {
		t.Fatal("a conflicting build must not leave an output file")
	}
}

func TestVerifyRejectsCorruptFile(t *testing.T) {
	dir := t.TempDir()
	csv := writeCSV(t, dir, testID(1)+";n:Data")
	out := filepath.Join(dir, "x.mphf")
	if err := runBuild([]string{"--input", csv, "--output", out}); err != nil {
		t.Fatal(err)
	}
	b, _ := os.ReadFile(out)
	b[len(b)-70] ^= 0xff
	bad := filepath.Join(dir, "bad.mphf")
	if err := os.WriteFile(bad, b, 0o600); err != nil {
		t.Fatal(err)
	}
	if err := runVerify([]string{bad}); err == nil {
		t.Fatal("expected verify to fail")
	}
}

func TestUsageErrors(t *testing.T) {
	if err := runVerify(nil); err == nil {
		t.Fatal("verify without path")
	}
	if err := runLookup([]string{"only-one"}); err == nil {
		t.Fatal("lookup without id")
	}
}
