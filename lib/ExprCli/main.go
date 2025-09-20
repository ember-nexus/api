package main

import (
	"encoding/json"
	"flag"
	"fmt"
	"os"

	"github.com/expr-lang/expr"
)

// Input structure
type Input struct {
	Expression string                 `json:"expression"`
	Parameters map[string]interface{} `json:"parameters"`
}

func main() {
	// Define flags
	filePath := flag.String("f", "", "path to JSON input file")
	flag.Usage = func() {
		fmt.Fprintf(flag.CommandLine.Output(),
			"Usage:\n  %[1]s '<json string>'\n  %[1]s -f <file>\n\n"+
				"Input JSON must have the form:\n"+
				`  {"expression": "x + 2", "parameters": {"x": 42}}`+"\n",
			os.Args[0])
		flag.PrintDefaults()
	}
	flag.Parse()

	// Enforce mutually exclusive input modes
	var inputJSON []byte
	var err error

	if *filePath != "" && flag.NArg() > 0 {
		exitWithError("cannot use both -f and a JSON argument")
	}

	switch {
	case *filePath != "":
		inputJSON, err = os.ReadFile(*filePath)
		if err != nil {
			exitWithError(fmt.Sprintf("failed to read file: %v", err))
		}
	case flag.NArg() == 1:
		inputJSON = []byte(flag.Arg(0))
	default:
		// No arguments: show help
		flag.Usage()
		os.Exit(1)
	}

	// Decode input JSON strictly (fail on unknown fields)
	var input Input
	dec := json.NewDecoder(bytesToReader(inputJSON))
	dec.DisallowUnknownFields()
	if err := dec.Decode(&input); err != nil {
		exitWithError(fmt.Sprintf("invalid input JSON: %v", err))
	}

	if input.Expression == "" {
		exitWithError("missing 'expression' field")
	}
	if input.Parameters == nil {
		input.Parameters = map[string]interface{}{}
	}

	// Compile expression
	program, err := expr.Compile(input.Expression, expr.Env(input.Parameters))
	if err != nil {
		exitWithError(fmt.Sprintf("compile error: %v", err))
	}

	// Run expression
	output, err := expr.Run(program, input.Parameters)
	if err != nil {
		exitWithError(fmt.Sprintf("runtime error: %v", err))
	}

	// Encode output as JSON
	jsonOutput, err := json.Marshal(output)
	if err != nil {
		exitWithError(fmt.Sprintf("JSON encoding error: %v", err))
	}

	fmt.Println(string(jsonOutput))
}

// Convert []byte to io.Reader for Decoder
func bytesToReader(b []byte) *os.File {
	r, w, _ := os.Pipe()
	go func() {
		w.Write(b)
		w.Close()
	}()
	return r
}

// Exit with JSON error message
func exitWithError(msg string) {
	errMsg, _ := json.Marshal(map[string]string{"error": msg})
	fmt.Fprintln(os.Stderr, string(errMsg))
	os.Exit(1)
}
