// Bridges an `astro:content` collection entry to its `?md`-compiled module.
//
// `entry.filePath` (a posix path relative to `config.root`, e.g.
// `src/data/pages/index.mdx` — set by the glob loader, see fact 3 in
// task.md) is also the key Astro's own `renderEntry` uses to look up the
// HTML-compiled module. `import.meta.glob` below builds the same kind of
// lookup table for the markdown-compiled ones.
import type { RootContent } from "mdast";

import { mdComponents } from "./registry/index.ts";

type MdxMarkdownModule = (props: {
  components: typeof mdComponents;
}) => RootContent | RootContent[];

const modules = import.meta.glob<MdxMarkdownModule>("/src/data/**/*.mdx", {
  query: "?md",
  import: "default",
});

/** Exposed for the Phase 7 coverage test — is there a `?md` module for this entry at all? */
export function hasMarkdownModule(entry: { filePath?: string }): boolean {
  return `/${entry.filePath}` in modules;
}

export async function renderMd(entry: {
  filePath?: string;
}): Promise<RootContent[]> {
  const load = modules[`/${entry.filePath}`];
  if (!load) throw new Error(`[mdmx] no markdown module for ${entry.filePath}`);
  const result = (await load())({ components: mdComponents });
  return Array.isArray(result) ? result : [result];
}
