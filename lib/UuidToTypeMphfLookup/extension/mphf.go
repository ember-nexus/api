// Package mphfext exposes the `.mphf` lookup table to PHP as the functions of the `EmberNexus\Mphf` namespace.
//
// The package is a FrankenPHP extension: the `mphf.go` source file is the input of `frankenphp extension-init`
// (done in the Docker build, see docker/api/Dockerfile), which generates the C glue next to it.
//
// Design rules (Go globals live as long as the process):
//   - the current table sits behind one atomic.Pointer, readers never lock;
//   - load() is serialised with a mutex and is idempotent per version; a file that fails validation never
//     replaces the current table;
//   - the mapping is released by the table's runtime cleanup once no reader references it anymore;
//   - no work in init(), no long-lived goroutines;
//   - every export recovers from panics and returns its neutral value; lookups also survive a faulting access to the
//     mapped file (Table.SafeLookup) and drop the table in that case;
//   - no pointers into PHP memory are kept, only non persistent PHP strings are returned.
//
//export_php:namespace EmberNexus\Mphf
package mphfext

// #include <Zend/zend_types.h>
import "C"
import (
	"encoding/json"
	"fmt"
	"runtime"
	"sync"
	"sync/atomic"
	"time"
	"unsafe"

	"github.com/dunglas/frankenphp"

	"github.com/ember-nexus/api/lib/UuidToTypeMphfLookup/mphf"
)

type loaded struct {
	table    *mphf.Table
	version  string
	loadedAt time.Time
}

var (
	current atomic.Pointer[loaded]
	loadMu  sync.Mutex
	lastErr atomic.Pointer[string]
)

func setError(msg string) { lastErr.Store(&msg) }

// gcPending makes sure that at most one delayed collection is scheduled, however often tables are swapped.
var gcPending atomic.Bool

// releaseOldMappingSoon lets the runtime release a replaced mapping (through its cleanup) soon instead of at the next
// natural GC. Swaps are rare, but a burst of them (e.g. a failing synchronisation loop) must not queue a full
// collection each, so at most one timer is pending; no goroutine stays around.
func releaseOldMappingSoon() {
	if gcPending.CompareAndSwap(false, true) {
		time.AfterFunc(2*time.Second, func() {
			gcPending.Store(false)
			runtime.GC()
		})
	}
}

func str(s string) unsafe.Pointer { return frankenphp.PHPString(s, false) }

// guard turns a panic inside an export into lastError().
func guard(where string) {
	if r := recover(); r != nil {
		setError(fmt.Sprintf("panic in %s: %v", where, r))
	}
}

//export_php:function load(string $path, string $version, bool $madviseRandom): bool
func load(path *C.zend_string, version *C.zend_string, madviseRandom bool) (ok bool) {
	defer func() {
		if r := recover(); r != nil {
			setError(fmt.Sprintf("panic in load: %v", r))
			ok = false
		}
	}()
	p := frankenphp.GoString(unsafe.Pointer(path))
	v := frankenphp.GoString(unsafe.Pointer(version))

	loadMu.Lock()
	defer loadMu.Unlock()
	if cur := current.Load(); cur != nil && cur.version == v {
		return true
	}
	t, err := mphf.Open(p, mphf.OpenOptions{MadviseRandom: madviseRandom})
	if err != nil {
		setError(err.Error())
		return false
	}
	old := current.Swap(&loaded{table: t, version: v, loadedAt: time.Now()})
	if old != nil {
		// let the runtime release the previous mapping soon instead of at the next natural GC; a one-shot timer, no
		// goroutine stays around
		releaseOldMappingSoon()
	}
	return true
}

//export_php:function unload(): void
func unload() {
	defer guard("unload")
	loadMu.Lock()
	defer loadMu.Unlock()
	if current.Swap(nil) != nil {
		releaseOldMappingSoon()
	}
}

//export_php:function version(): string
func version() (ret unsafe.Pointer) {
	defer func() {
		if r := recover(); r != nil {
			setError(fmt.Sprintf("panic in version: %v", r))
			ret = str("")
		}
	}()
	if cur := current.Load(); cur != nil {
		return str(cur.version)
	}
	return str("")
}

//export_php:function lookup(string $id): string
func lookup(id *C.zend_string) (ret unsafe.Pointer) {
	defer func() {
		if r := recover(); r != nil {
			setError(fmt.Sprintf("panic in lookup: %v", r))
			ret = str("")
		}
	}()
	cur := current.Load()
	if cur == nil {
		return str("")
	}
	typ, _, err := cur.table.SafeLookup(frankenphp.GoString(unsafe.Pointer(id)))
	runtime.KeepAlive(cur)
	if err != nil {
		// the file was truncated or its storage vanished while it is mapped: every further access would fault as
		// well, so the table is dropped; PHP sees an unloaded table, answers without it and loads a good file again
		setError(err.Error())
		if current.CompareAndSwap(cur, nil) {
			releaseOldMappingSoon()
		}
		return str("")
	}
	return str(typ)
}

//export_php:function lastError(): string
func lastError() unsafe.Pointer {
	if e := lastErr.Load(); e != nil {
		return str(*e)
	}
	return str("")
}

//export_php:function info(): string
func info() (ret unsafe.Pointer) {
	defer func() {
		if r := recover(); r != nil {
			setError(fmt.Sprintf("panic in info: %v", r))
			ret = str("{}")
		}
	}()
	cur := current.Load()
	if cur == nil {
		return str("{}")
	}
	b, err := json.Marshal(struct {
		Version  string `json:"version"`
		LoadedAt int64  `json:"loadedAt"`
		mphf.Info
	}{cur.version, cur.loadedAt.Unix(), cur.table.Info()})
	runtime.KeepAlive(cur)
	if err != nil {
		setError(err.Error())
		return str("{}")
	}
	return str(string(b))
}
