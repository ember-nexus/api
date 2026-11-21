// Package uuidtotypemphflookup is the root of the Go module behind the element id -> type lookup.
//
// It contains no code itself: the lookup structure lives in package mphf, the command line tools in cmd/ and the
// FrankenPHP extension in the nested module extension/. This file only exists because the Docker build (xcaddy)
// imports the module's root package when it is added with `--with`.
package uuidtotypemphflookup
