// Command mphf-gen writes random sv2a7 shaped `<id>;<type>` CSV data for tests and benchmarks.
//
//	mphf-gen --count 10000000 --types 30 > elements.csv
//	mphf-gen --count 1000 --ids-only > ids.txt     (ids only, for lookup benchmarks)
package main

import (
	"bufio"
	"flag"
	"fmt"
	"math/rand/v2"
	"os"
)

const alphabet = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz"

func main() {
	count := flag.Int("count", 1000, "number of elements")
	types := flag.Int("types", 30, "number of distinct types (zipf-ish distribution)")
	seed := flag.Uint64("seed", 1, "random seed")
	idsOnly := flag.Bool("ids-only", false, "write only ids")
	flag.Parse()

	rng := rand.New(rand.NewPCG(*seed, 0xe3b0c44298fc1c14))
	zipf := rand.NewZipf(rand.New(rand.NewPCG(*seed, 7)), 1.2, 4, uint64(*types-1))
	w := bufio.NewWriterSize(os.Stdout, 1<<20)
	defer w.Flush()
	var id [37]byte
	copy(id[:6], "02IiNr")
	ts := uint64(1_760_000_000_000)
	for i := 0; i < *count; i++ {
		ts += uint64(rng.IntN(3))
		t := ts
		for j := 13; j >= 6; j-- {
			id[j] = alphabet[t%62]
			t /= 62
		}
		r := rng.Uint64()
		for j := 14; j < 37; j++ {
			if j%10 == 4 {
				r = rng.Uint64()
			}
			id[j] = alphabet[r%62]
			r /= 62
		}
		if *idsOnly {
			fmt.Fprintf(w, "%s\n", id[:])
		} else {
			fmt.Fprintf(w, "%s;Type%03d\n", id[:], zipf.Uint64())
		}
	}
}
