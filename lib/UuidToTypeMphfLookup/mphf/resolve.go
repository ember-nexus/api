package mphf

import (
	"errors"
	"io"
	"strings"
)

// DefaultBuildAttempts is the number of builds BuildFileResolvingConflicts tries.
const DefaultBuildAttempts = 5

// keyHook is a test-only hook: when set, it replaces the id before it is hashed (used to force hash collisions of
// different ids). It must stay nil in production code.
var keyHook func(id string) string

// IsDataConflict tells whether the lines (`<id>;<type>`, as returned by ConflictError.FindIDs) contain the same id
// with different types. That is a conflict in the input data, which no rebuild can solve. Lines of different ids with
// the same hash are a hash collision instead, which a new random hash key avoids.
func IsDataConflict(lines []string) bool {
	typeOf := map[string]string{}
	for _, line := range lines {
		id, typ, ok := strings.Cut(line, ";")
		if !ok {
			continue
		}
		if prev, seen := typeOf[id]; seen && prev != typ {
			return true
		}
		typeOf[id] = typ
	}
	return false
}

// BuildFileResolvingConflicts reads the CSV from open() and builds path. If the build fails with a ConflictError
// which is caused by a hash collision of two different ids (about 1e-23 per build at 100M ids), the build is repeated:
// every build uses a new random hash key, so the same ids collide again with negligible probability. A conflict of the
// input data (same id, different types) is returned as it is, listing up to 20 of the lines in the error. onRetry is
// called before each repeated build; it may be nil.
func BuildFileResolvingConflicts(opts BuildOptions, open func() (io.ReadCloser, error), path string, attempts int, onRetry func(attempt int)) (*Stats, int64, error) {
	if attempts < 1 {
		attempts = DefaultBuildAttempts
	}
	for attempt := 1; ; attempt++ {
		b, err := NewBuilder(opts)
		if err != nil {
			return nil, 0, err
		}
		in, err := open()
		if err != nil {
			return nil, 0, err
		}
		lines, err := b.ReadCSV(in)
		_ = in.Close()
		if err != nil {
			return nil, 0, err
		}
		stats, err := b.BuildFile(path)
		if err == nil {
			return stats, lines, nil
		}
		var ce *ConflictError
		if !errors.As(err, &ce) {
			return nil, 0, err
		}
		in, ferr := open()
		if ferr != nil {
			return nil, 0, err
		}
		found, _ := ce.FindIDs(in, 20)
		_ = in.Close()
		if IsDataConflict(found) || attempt >= attempts {
			return nil, 0, &DataConflictError{ConflictError: ce, Lines: found, Attempts: attempt}
		}
		if onRetry != nil {
			onRetry(attempt)
		}
	}
}

// DataConflictError is returned by BuildFileResolvingConflicts if a rebuild can not solve the conflict.
type DataConflictError struct {
	*ConflictError
	// Lines are some of the conflicting input lines.
	Lines []string
	// Attempts is the number of builds which were tried.
	Attempts int
}

func (e *DataConflictError) Unwrap() error { return e.ConflictError }
