import type { RootContent } from "mdast";

/** Markdown port of `Check.astro` — a plain checkmark, not the ✅ emoji. */
export function Check(): RootContent {
  return { type: "text", value: "✓" };
}
