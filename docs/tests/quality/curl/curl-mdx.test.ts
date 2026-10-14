import { describe, it, expect } from "vitest";
import { readdirSync, readFileSync } from "fs";
import { join } from "path";
import { curlfmt } from "../../../src/util/curl-fmt";

// ── MDX discovery ──

const SRC_DATA_DIR = join(import.meta.dirname, "../../../src/data");

function findMdxFiles(dir: string): string[] {
  const entries = readdirSync(dir, { recursive: true, withFileTypes: false }) as string[];
  return entries
    .filter((f) => f.endsWith(".mdx"))
    .map((f) => join(dir, f));
}

// ── code-block extraction ──

interface CodeBlock {
  /** 0-based line index of the opening fence (``` line) */
  openLine: number;
  lang: string;
  content: string;
}

function extractCodeBlocks(source: string): CodeBlock[] {
  const lines = source.split("\n");
  const blocks: CodeBlock[] = [];
  let i = 0;

  while (i < lines.length) {
    const line = lines[i];
    // Match opening fence: optional leading spaces + ``` + language tag + optional annotations
    const fenceMatch = line.match(/^(\s*)```(\w+)(?:\s.*)?$/);
    if (fenceMatch) {
      const indent = fenceMatch[1];
      const lang = fenceMatch[2];
      const openLine = i;
      const contentLines: string[] = [];
      i++;
      while (i < lines.length) {
        // Closing fence must match same indentation + ```
        if (lines[i].startsWith(indent + "```") && !lines[i].match(/^(\s*)```\w+/)) {
          break;
        }
        contentLines.push(lines[i]);
        i++;
      }
      blocks.push({ openLine, lang, content: contentLines.join("\n") });
    }
    i++;
  }

  return blocks;
}

// ── test case building ──

interface CurlTestCase {
  /** Relative path shown in test names and failure messages */
  relPath: string;
  absPath: string;
  /** 1-based line number of the opening ``` fence */
  line: number;
  curlSource: string;
}

const mdxFiles = findMdxFiles(SRC_DATA_DIR);

const testCases: CurlTestCase[] = mdxFiles.flatMap((absPath) => {
  const relPath = absPath.replace(SRC_DATA_DIR + "/", "");
  const source = readFileSync(absPath, "utf-8");
  const blocks = extractCodeBlocks(source);

  return blocks
    .filter((b) => b.lang === "bash" && b.content.trimStart().startsWith("curl"))
    .map((b) => ({
      relPath,
      absPath,
      line: b.openLine + 1, // convert to 1-based
      curlSource: b.content.trim(),
    }));
});

// ── vitest suite ──

describe("curl code blocks in MDX files — formatting", () => {
  it("at least one curl block was found", () => {
    expect(testCases.length).toBeGreaterThan(0);
  });

  describe.each(testCases)("$relPath : line $line", ({ relPath, line, curlSource }) => {
    it("is correctly formatted", () => {
      let formatted: string;
      try {
        formatted = curlfmt(curlSource, { domainWhitelist: ["api.example.com"] });
      } catch (err: unknown) {
        const msg = err instanceof Error ? err.message : String(err);
        throw new Error(
          `curl formatter threw an error for block at ${relPath}:${line}\n` +
            `Error: ${msg}\n` +
            `\nSource block:\n${curlSource}`,
        );
      }

      if (formatted !== curlSource) {
        const sourceLines = curlSource.split("\n");
        const formattedLines = formatted.split("\n");
        const maxLen = Math.max(sourceLines.length, formattedLines.length);
        const diffLines: string[] = [];

        for (let i = 0; i < maxLen; i++) {
          const src = sourceLines[i] ?? "<missing>";
          const fmt = formattedLines[i] ?? "<missing>";
          if (src !== fmt) {
            diffLines.push(`  line ${i + 1}:`);
            diffLines.push(`    - ${src}`);
            diffLines.push(`    + ${fmt}`);
          }
        }

        throw new Error(
          `curl block at ${relPath}:${line} is not correctly formatted.\n` +
            `\nDiff (- current, + expected):\n${diffLines.join("\n")}` +
            `\n\nExpected complete block:\n${formatted}`,
        );
      }
    });
  });
});
