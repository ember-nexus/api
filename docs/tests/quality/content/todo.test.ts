import { existsSync, readFileSync, readdirSync } from "fs";
import { join } from "path";

import { describe, expect, it } from "vitest";

// Catches leftover "todo" placeholders in shipped content (e.g. an unfinished "Links, todo:"
// section) before they reach the built site. Scans the *built* markdown output, not the .mdx
// source, so it also catches anything a component/render step might introduce.
// Requires `pnpm run build` (or `task check:build`) to have run first.

const DIST_ROOT = join(import.meta.dirname, "../../../dist");
const TODO_RE = /todo/i;

function findMdFiles(dir: string): string[] {
  return (
    readdirSync(dir, { recursive: true, withFileTypes: false }) as string[]
  )
    .filter((f) => f.endsWith(".md"))
    .map((f) => join(dir, f));
}

interface TodoCase {
  relPath: string;
  line: number;
  text: string;
}

function collectTodos(files: string[]): TodoCase[] {
  const cases: TodoCase[] = [];
  for (const absPath of files) {
    const relPath = absPath.replace(DIST_ROOT + "/", "");
    const lines = readFileSync(absPath, "utf-8").split("\n");
    lines.forEach((line, i) => {
      if (TODO_RE.test(line)) {
        cases.push({ relPath, line: i + 1, text: line.trim() });
      }
    });
  }
  return cases;
}

describe("dist — no leftover TODOs in content", () => {
  if (!existsSync(DIST_ROOT)) {
    it("dist/ exists", () => {
      throw new Error(
        "dist/ does not exist — run `pnpm run build` (or `task check:build`) before this suite.",
      );
    });
    return;
  }

  const todos = collectTodos(findMdFiles(DIST_ROOT));

  it("at least one markdown file was scanned", () => {
    expect(findMdFiles(DIST_ROOT).length).toBeGreaterThan(0);
  });

  it("no file contains the word 'todo'", () => {
    if (todos.length === 0) return;
    const report = todos
      .map(({ relPath, line, text }) => `  ${relPath}:${line} — ${text}`)
      .join("\n");
    throw new Error(`found leftover "todo" content:\n${report}`);
  });
});
