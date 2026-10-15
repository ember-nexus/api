import type { RootContent } from "mdast";

/** Markdown port of `Missing.astro`. */
export function Missing(): RootContent {
  return { type: "text", value: "➖" };
}
