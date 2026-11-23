// Command mphf-bench measures lookup latency of a `.mphf` file against ids taken from a CSV file.
//
//	mphf-bench --mphf x.mphf --csv x.csv [--samples 1000000] [--lookups 20000000] [--threads 1] [--madvise-random]
package main

import (
	"bufio"
	"flag"
	"fmt"
	"math/rand/v2"
	"os"
	"sort"
	"sync"
	"time"

	"github.com/ember-nexus/api/lib/UuidToTypeMphfLookup/mphf"
)

func main() {
	path := flag.String("mphf", "", ".mphf file")
	csv := flag.String("csv", "", "CSV file the ids are sampled from")
	samples := flag.Int("samples", 1_000_000, "number of ids sampled from the CSV (evenly spread)")
	lookups := flag.Int("lookups", 20_000_000, "number of lookups per thread for the throughput run")
	threads := flag.Int("threads", 1, "parallel goroutines for the throughput run")
	madv := flag.Bool("madvise-random", false, "madvise(MADV_RANDOM) the values section")
	flag.Parse()

	t0 := time.Now()
	tab, err := mphf.Open(*path, mphf.OpenOptions{MadviseRandom: *madv})
	if err != nil {
		fatal(err)
	}
	fmt.Printf("open: %v, %+v\n", time.Since(t0), tab.Info())

	f, err := os.Open(*csv)
	if err != nil {
		fatal(err)
	}
	var all []string
	sc := bufio.NewScanner(f)
	sc.Buffer(make([]byte, 1<<20), 1<<20)
	total := int(tab.Info().KeyCount)
	step := max(total / *samples, 1)
	for i := 0; sc.Scan(); i++ {
		if i%step == 0 {
			all = append(all, sc.Text()[:mphf.IDLength])
		}
	}
	_ = f.Close()
	fmt.Printf("sampled %d ids\n", len(all))
	rng := rand.New(rand.NewPCG(1, 2))
	rng.Shuffle(len(all), func(i, j int) { all[i], all[j] = all[j], all[i] })

	// per lookup latency (includes ~25ns of time.Now overhead)
	n := min(len(all), 2_000_000)
	lat := make([]int64, n)
	for i := 0; i < n; i++ {
		s := time.Now()
		tab.Lookup(all[i])
		lat[i] = int64(time.Since(s))
	}
	sort.Slice(lat, func(i, j int) bool { return lat[i] < lat[j] })
	fmt.Printf("latency (incl. timer overhead): p50=%dns p99=%dns p999=%dns max=%dns\n", lat[n/2], lat[n*99/100], lat[n*999/1000], lat[n-1])

	// throughput
	var wg sync.WaitGroup
	start := time.Now()
	for g := 0; g < *threads; g++ {
		wg.Add(1)
		go func() {
			defer wg.Done()
			r := rand.New(rand.NewPCG(uint64(g)+10, 3))
			miss := 0
			for i := 0; i < *lookups; i++ {
				if _, ok := tab.Lookup(all[r.IntN(len(all))]); !ok {
					miss++
				}
			}
			if miss > 0 {
				fmt.Println("MISSES:", miss)
			}
		}()
	}
	wg.Wait()
	el := time.Since(start)
	cnt := *lookups * *threads
	fmt.Printf("throughput: %d lookups, %d threads: %.1f ns/lookup/thread, %.2f M lookups/s total\n",
		cnt, *threads, float64(el.Nanoseconds())/float64(*lookups), float64(cnt)/el.Seconds()/1e6)
}

func fatal(err error) {
	fmt.Fprintln(os.Stderr, "error:", err)
	os.Exit(1)
}
