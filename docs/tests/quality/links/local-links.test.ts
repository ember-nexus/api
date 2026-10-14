import { existsSync, readFileSync, readdirSync } from "fs";
import { dirname, extname, join } from "path";

import { parse } from "node-html-parser";
import { describe, expect, it } from "vitest";

// This suite checks the *built* site, not the .mdx source — every internal href/src in the
// generated .html and .md files (plus llms.txt) must resolve to a real file in dist/. It only
// checks local links; external ones are covered by tests/quality/links/external-links.test.ts.
// Requires `pnpm run build` (or `task check:build`) to have run first.

const DIST_ROOT = join(import.meta.dirname, "../../../dist");

// ── helpers ──

function findFiles(dir: string, suffix: string): string[] {
  return (
    readdirSync(dir, { recursive: true, withFileTypes: false }) as string[]
  )
    .filter((f) => f.endsWith(suffix))
    .map((f) => join(dir, f));
}

/** True for links this test doesn't apply to: same-page fragments, and anything with a URL scheme (http:, mailto:, //cdn, ...). */
function isSkippable(raw: string): boolean {
  if (!raw || raw.startsWith("#") || raw.startsWith("//")) return true;
  return /^[a-z][a-z0-9+.-]*:/i.test(raw);
}

function stripQueryAndHash(raw: string): string {
  return raw.split("#")[0].split("?")[0];
}

/**
 * Resolve a local link to the dist file it should point at.
 * Root-relative links (`/foo`) resolve against dist/; anything else resolves against the
 * directory of the file containing the link. Extensionless links are page routes, which Astro
 * emits as `<path>/index.html` — markdown output is always linked with an explicit `.md`.
 */
function resolveLocalTarget(fileDir: string, raw: string): string {
  const clean = decodeURIComponent(stripQueryAndHash(raw));
  const target = clean.startsWith("/")
    ? join(DIST_ROOT, clean)
    : join(fileDir, clean);
  if (extname(target) !== "") return target;
  return join(target, "index.html");
}

function shorten(text: string, max: number): string {
  return text.length > max ? text.slice(0, max) + "…" : text;
}

// ── link extraction ──

function extractHtmlLinks(absPath: string): string[] {
  const root = parse(readFileSync(absPath, "utf-8"));
  const links: string[] = [];
  for (const el of root.querySelectorAll(
    "a[href], link[href], img[src], script[src], source[src]",
  )) {
    const v = el.getAttribute("href") ?? el.getAttribute("src");
    if (v) links.push(v);
    const srcset = el.getAttribute("srcset");
    if (srcset) {
      for (const entry of srcset.split(",")) {
        const url = entry.trim().split(/\s+/)[0];
        if (url) links.push(url);
      }
    }
  }
  return links;
}

// Captures the URL out of `[text](url)` / `[text](url "title")` / `[text](url 'title')`.
// The URL itself is assumed not to contain `)` or whitespace (true for every link in this
// project) so a trailing `)` from the surrounding prose, e.g. "(see the [docs](/url))", isn't
// swallowed into the match.
const MD_LINK_RE = /\[[^\]]*\]\(([^\s)]+)(?:\s+(?:"[^"]*"|'[^']*'))?\)/g;

function extractMdLinks(absPath: string): string[] {
  const text = readFileSync(absPath, "utf-8");
  return [...text.matchAll(MD_LINK_RE)].map((m) => m[1]);
}

// ── case collection (deduplicated by resolved target) ──

interface LinkCase {
  relPath: string;
  relPathShort: string;
  link: string;
  linkShort: string;
  resolvedPath: string;
}

function collectCases(
  files: string[],
  extract: (absPath: string) => string[],
): LinkCase[] {
  const cases = new Map<string, LinkCase>();
  for (const absPath of files) {
    const relPath = absPath.replace(DIST_ROOT + "/", "");
    const dir = dirname(absPath);
    for (const raw of extract(absPath)) {
      if (isSkippable(raw)) continue;
      const resolvedPath = resolveLocalTarget(dir, raw);
      const key = resolvedPath;
      if (cases.has(key)) continue;
      cases.set(key, {
        relPath,
        relPathShort: shorten(relPath, 50),
        link: raw,
        linkShort: shorten(raw, 60),
        resolvedPath,
      });
    }
  }
  return [...cases.values()];
}

// ── suite ──

describe("dist — local link integrity", () => {
  if (!existsSync(DIST_ROOT)) {
    it("dist/ exists", () => {
      throw new Error(
        "dist/ does not exist — run `pnpm run build` (or `task check:build`) before this suite.",
      );
    });
    return;
  }

  const htmlFiles = findFiles(DIST_ROOT, ".html");
  const mdFiles = findFiles(DIST_ROOT, ".md");
  const llmsTxt = join(DIST_ROOT, "llms.txt");
  if (existsSync(llmsTxt)) mdFiles.push(llmsTxt);

  const htmlCases = collectCases(htmlFiles, extractHtmlLinks);
  const mdCases = collectCases(mdFiles, extractMdLinks);

  describe(".html files", () => {
    it("at least one internal link was found", () => {
      expect(htmlCases.length).toBeGreaterThan(0);
    });

    it.each(htmlCases)(
      "$relPathShort — $linkShort",
      ({ relPath, link, resolvedPath }) => {
        if (!existsSync(resolvedPath)) {
          const err = new Error(
            `file ${relPath} links to ${link}, which resolves to missing file ${resolvedPath}`,
          );
          err.stack = err.message;
          throw err;
        }
      },
    );
  });

  describe(".md files and llms.txt", () => {
    it("at least one internal link was found", () => {
      expect(mdCases.length).toBeGreaterThan(0);
    });

    it.each(mdCases)(
      "$relPathShort — $linkShort",
      ({ relPath, link, resolvedPath }) => {
        if (!existsSync(resolvedPath)) {
          const err = new Error(
            `file ${relPath} links to ${link}, which resolves to missing file ${resolvedPath}`,
          );
          err.stack = err.message;
          throw err;
        }
      },
    );
  });
});
