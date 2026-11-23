package mphf

import (
	"errors"
	"fmt"
	"runtime/debug"
)

// ErrMappingFault is returned if reading the memory mapped file faulted, i.e. the file was truncated or its storage
// vanished after it was mapped (the kernel answers such accesses with SIGBUS). The table is unusable afterwards and
// has to be closed and reopened.
var ErrMappingFault = errors.New("the memory mapped mphf file can no longer be read")

// faultError converts a recovered panic into ErrMappingFault if the runtime raised it for a faulting memory access
// (a runtime.Error with an Addr method, see debug.SetPanicOnFault); everything else is panicked again.
func faultError(r any) error {
	if e, ok := r.(interface{ Addr() uintptr }); ok {
		return fmt.Errorf("%w (fault at address %#x)", ErrMappingFault, e.Addr())
	}
	panic(r)
}

// SafeLookup is Lookup, but a faulting access to the mapping (e.g. the file was truncated while it is mapped) results
// in ErrMappingFault instead of crashing the process. Use it wherever the file is not owned exclusively.
func (t *Table) SafeLookup(id string) (typ string, ok bool, err error) {
	defer func() {
		if r := recover(); r != nil {
			typ, ok, err = "", false, faultError(r)
		}
	}()
	defer debug.SetPanicOnFault(debug.SetPanicOnFault(true))
	typ, ok = t.Lookup(id)
	return typ, ok, nil
}

// parseFileGuarded is parseFile with the protection of SafeLookup; the hash check reads the whole mapping.
func parseFileGuarded(file []byte, verifyHash bool) (t *Table, err error) {
	defer func() {
		if r := recover(); r != nil {
			t, err = nil, faultError(r)
		}
	}()
	defer debug.SetPanicOnFault(debug.SetPanicOnFault(true))
	return parseFile(file, verifyHash)
}
