import { describe, it, expect } from "vitest";
import {
  curlfmt,
  parse,
  splitPipe,
  tokenize,
  extractDomain,
  validateDomain,
  format,
  CurlValidationError,
} from "../../../src/util/curl-fmt";

// ── tokenizer ──

describe("tokenize", () => {
  it("handles single-quoted strings", () => {
    expect(tokenize("curl 'https://example.com'")).toEqual(["curl", "https://example.com"]);
  });

  it("handles double-quoted strings with escapes", () => {
    expect(tokenize('curl "hello \\"world\\""')).toEqual(["curl", 'hello "world"']);
  });

  it("skips backslash-newline continuations", () => {
    expect(tokenize("curl \\\nhttps://example.com")).toEqual(["curl", "https://example.com"]);
  });
});

// ── splitPipe ──

describe("splitPipe", () => {
  it("splits on unquoted pipe", () => {
    const r = splitPipe("curl https://x.com | jq '.'");
    expect(r.curl).toBe("curl https://x.com");
    expect(r.pipe).toBe("| jq '.'");
  });

  it("ignores pipe inside quotes", () => {
    const r = splitPipe("curl -d 'a|b' https://x.com");
    expect(r.curl).toBe("curl -d 'a|b' https://x.com");
    expect(r.pipe).toBe("");
  });

  it("returns empty pipe when no pipe present", () => {
    const r = splitPipe("curl https://x.com");
    expect(r.pipe).toBe("");
  });

  it("handles multiple pipes", () => {
    const r = splitPipe("curl https://x.com | jq . | head -n1");
    expect(r.curl).toBe("curl https://x.com");
    expect(r.pipe).toBe("| jq . | head -n1");
  });
});

// ── parse ──

describe("parse", () => {
  it("parses a basic GET", () => {
    const p = parse("curl https://api.example.com/users");
    expect(p.method).toBe("GET");
    expect(p.url).toBe("https://api.example.com/users");
    expect(p.headers).toEqual([]);
  });

  it("parses headers", () => {
    const p = parse("curl -H 'Authorization: Bearer xyz' -H 'Accept: application/json' https://x.com");
    expect(p.headers).toHaveLength(2);
    expect(p.headers[0]).toEqual({ name: "Authorization", value: "Bearer xyz" });
    expect(p.headers[1]).toEqual({ name: "Accept", value: "application/json" });
  });

  it("parses POST with data", () => {
    const p = parse(`curl -X POST -d '{"a":1}' https://x.com`);
    expect(p.method).toBe("POST");
    expect(p.data).toEqual({ flag: "-d", value: '{"a":1}' });
  });

  it("infers POST when -d is present without -X", () => {
    const p = parse(`curl -d '{"a":1}' https://x.com`);
    expect(p.method).toBe("POST");
  });

  it("preserves boolean flags", () => {
    const p = parse("curl -s --compressed https://x.com");
    expect(p.flags).toEqual([{ flag: "-s" }, { flag: "--compressed" }]);
  });

  it("preserves valued flags", () => {
    const p = parse("curl -u admin:pass https://x.com");
    expect(p.flags).toEqual([{ flag: "-u", value: "admin:pass" }]);
  });

  it("extracts pipe tail", () => {
    const p = parse("curl https://x.com | jq '.data'");
    expect(p.pipe).toBe("| jq '.data'");
  });

  it("throws on empty input", () => {
    expect(() => parse("")).toThrow(CurlValidationError);
    expect(() => parse("   ")).toThrow(CurlValidationError);
  });

  it("throws when no URL is found", () => {
    expect(() => parse("curl -H 'X: Y'")).toThrow("No URL found");
  });

  it("records malformed headers as errors", () => {
    const p = parse("curl -H 'BadHeader' https://x.com");
    expect(p.errors).toContain("Malformed header: BadHeader");
  });

  it("parses unquoted URL containing whitespace", () => {
    const p = parse("curl https://api.example.com/<uuid of element>/parents");
    expect(p.url).toBe("https://api.example.com/<uuid of element>/parents");
    expect(p.errors).toEqual([]);
  });

  it("parses unquoted URL with whitespace followed by flags", () => {
    const p = parse("curl -s https://api.example.com/<uuid of element>/parents --compressed");
    expect(p.url).toBe("https://api.example.com/<uuid of element>/parents");
    expect(p.flags).toEqual([{ flag: "-s" }, { flag: "--compressed" }]);
  });

  it("parses --url value containing whitespace", () => {
    const p = parse("curl --url https://api.example.com/<uuid of element>/parents");
    expect(p.url).toBe("https://api.example.com/<uuid of element>/parents");
  });
});

// ── extractDomain ──

describe("extractDomain", () => {
  it("extracts from a full URL", () => {
    expect(extractDomain("https://api.example.com/v1/users")).toBe("api.example.com");
  });

  it("extracts from URL with port", () => {
    expect(extractDomain("http://localhost:3000/test")).toBe("localhost");
  });

  it("returns null for garbage", () => {
    expect(extractDomain("not-a-url")).toBeNull();
  });
});

// ── validateDomain ──

describe("validateDomain", () => {
  it("passes for whitelisted domain", () => {
    expect(() => validateDomain("https://api.example.com/v1", ["api.example.com"])).not.toThrow();
  });

  it("passes for subdomain of whitelisted domain", () => {
    expect(() => validateDomain("https://staging.api.example.com/v1", ["api.example.com"])).not.toThrow();
  });

  it("throws for non-whitelisted domain", () => {
    expect(() => validateDomain("https://evil.com/steal", ["api.example.com"])).toThrow(CurlValidationError);
    expect(() => validateDomain("https://evil.com/steal", ["api.example.com"])).toThrow("not in the whitelist");
  });

  it("does nothing with empty whitelist", () => {
    expect(() => validateDomain("https://anything.com", [])).not.toThrow();
  });
});

// ── format ──

describe("format", () => {
  it("puts URL last", () => {
    const p = parse("curl https://x.com -s");
    const out = format(p);
    const lines = out.split("\n");
    expect(lines[lines.length - 1].trim()).toBe("https://x.com");
  });

  it("attaches -v to the first line and omits redundant -X GET", () => {
    const p = parse("curl -X GET -v https://x.com");
    const out = format(p);
    expect(out.split("\n")[0]).toMatch(/^curl -v/);
    expect(out).not.toMatch(/^\s+-v\s*(\\)?$/m);
  });

  it("attaches -v to first line and omits default GET method", () => {
    const p = parse("curl -v https://x.com");
    const out = format(p);
    expect(out.split("\n")[0]).toMatch(/^curl -v/);
  });

  it("uses -I instead of -X HEAD", () => {
    const p = parse("curl -X HEAD -v https://x.com");
    const out = format(p);
    expect(out.split("\n")[0]).toMatch(/^curl -I -v/);
    expect(out).not.toContain("-X HEAD");
  });

  it("adds a continuation backslash before a pipe tail", () => {
    const p = parse("curl https://x.com | jq '.'");
    const out = format(p);
    const lines = out.split("\n");
    expect(lines[lines.length - 2]).toMatch(/\\$/);
    expect(lines[lines.length - 1]).toMatch(/^  \| jq/);
  });

  it("sorts headers by priority", () => {
    const p = parse("curl -H 'X-Custom: 1' -H 'Authorization: Bearer x' https://x.com");
    const out = format(p, { headerPriority: ["Authorization", "X-Custom"] });
    const hLines = out.split("\n").filter((l) => l.includes("-H"));
    expect(hLines[0]).toContain("Authorization");
    expect(hLines[1]).toContain("X-Custom");
  });

  it("pretty-prints JSON body", () => {
    const p = parse(`curl -d '{"a":1,"b":2}' https://x.com`);
    const out = format(p, { prettyJson: true });
    expect(out).toContain('"a": 1');
    expect(out).toContain('"b": 2');
  });

  it("uses 6-space indent for JSON body", () => {
    const p = parse(`curl -d '{"key":"val"}' https://x.com`);
    const out = format(p, { prettyJson: true });
    const jsonLine = out.split("\n").find((l) => l.includes('"key"'));
    expect(jsonLine).toMatch(/^ {6}/); // exactly 6 leading spaces
  });

  it("keeps JSON body as-is when prettyJson is false", () => {
    const p = parse(`curl -d '{"a":1,"b":2}' https://x.com`);
    const out = format(p, { prettyJson: false });
    expect(out).not.toContain("\n       ");
  });

  it("uses --data-binary for file uploads", () => {
    const p = parse("curl -d @./image.jpg https://x.com");
    const out = format(p);
    expect(out).toContain("--data-binary @./image.jpg");
    expect(out).not.toContain("-d @./image.jpg");
  });

  it("preserves --data-binary for file uploads", () => {
    const p = parse("curl --data-binary @./image.jpg https://x.com");
    const out = format(p);
    expect(out).toContain("--data-binary @./image.jpg");
  });

  it("appends pipe tail", () => {
    const p = parse("curl https://x.com | jq '.'");
    const out = format(p);
    expect(out).toContain("| jq '.'");
  });

  it("preserves multi-pipe chains", () => {
    const p = parse("curl https://x.com | jq . | head -n5");
    const out = format(p);
    expect(out).toContain("| jq . | head -n5");
  });

  it("single-quotes URL containing whitespace", () => {
    const p = parse("curl https://api.example.com/<uuid of element>/parents");
    const out = format(p);
    expect(out).toContain("'https://api.example.com/<uuid of element>/parents'");
  });
});

// ── curlfmt (main entry) ──

describe("curlfmt", () => {
  it("returns formatted output for valid command", () => {
    const out = curlfmt("curl https://api.example.com/users", {
      domainWhitelist: ["api.example.com"],
    });
    expect(out.startsWith("curl")).toBe(true);
    expect(out).toContain("api.example.com/users");
  });

  it("throws CurlValidationError for bad domain", () => {
    expect(() =>
      curlfmt("curl https://evil.com/x", {
        domainWhitelist: ["api.example.com"],
      })
    ).toThrow(CurlValidationError);
  });

  it("returns null-safe: throws on empty input", () => {
    expect(() => curlfmt("")).toThrow(CurlValidationError);
  });

  it("formats URL containing whitespace", () => {
    const out = curlfmt(
      "curl -X GET -H 'Authorization: Bearer secret-token:PIPeJGUt7c00ENn8a5uDlc' https://api.example.com/<uuid of element>/parents",
      { domainWhitelist: ["api.example.com"] },
    );
    expect(out.startsWith("curl")).toBe(true);
    expect(out).toContain("'https://api.example.com/<uuid of element>/parents'");
  });

  it("full round-trip with all features", () => {
    const input = `curl -X POST 'https://api.example.com/v1/data' \
      -H 'Accept: application/json' \
      -H 'Authorization: Bearer token123' \
      -d '{"query":"test","limit":10}' \
      --compressed -s | jq '.results'`;

    const out = curlfmt(input, {
      headerPriority: ["Authorization", "Accept"],
      domainWhitelist: ["api.example.com"],
      prettyJson: true,
    });

    // method first (POST is implied by -d, so no -X POST)
    expect(out).toMatch(/^curl/);
    expect(out).not.toContain("-X POST");
    // auth before accept
    const authIdx = out.indexOf("Authorization");
    const acceptIdx = out.indexOf("Accept");
    expect(authIdx).toBeLessThan(acceptIdx);
    // pretty json
    expect(out).toContain('"query": "test"');
    // pipe preserved
    expect(out).toContain("| jq '.results'");
    // url last (before pipe)
    const lines = out.split("\n");
    const urlLine = lines.find((l) => l.includes("api.example.com"));
    const pipeLine = lines.find((l) => l.includes("| jq"));
    expect(lines.indexOf(urlLine!)).toBeLessThan(lines.indexOf(pipeLine!));
  });
});
