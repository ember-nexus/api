// A handful of swagger-derived fields (parameter/header/response descriptions)
// are themselves markdown source strings — the HTML pipeline runs them
// through `marked.parse()` / `renderMarkdown()`. The markdown target has no
// use for an HTML string; it needs the same source parsed back into mdast so
// it can be spliced directly into the surrounding tree (preserving links,
// emphasis, etc. instead of dumping escaped literal text).
import type { Root, RootContent } from "mdast";
import remarkGfm from "remark-gfm";
import remarkParse from "remark-parse";
import { unified } from "unified";

const processor = unified().use(remarkParse).use(remarkGfm);

export function parseMarkdownSource(source: string): RootContent[] {
  if (!source) return [];
  const tree = processor.parse(source) as Root;
  return tree.children;
}
