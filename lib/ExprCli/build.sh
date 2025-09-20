#!/bin/sh

# apk add upx

go mod download
go build -ldflags="-s -w" -o expr-cli main.go
upx --best --lzma expr-cli
mv -f expr-cli ../../bin/expr-cli
