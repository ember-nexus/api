// Turns the mdast tree produced by the `mdmx` JSX runtime into CommonMark/GFM
// text. This is the only place in the markdown render path that touches
// serialization — no HTML is ever produced or parsed on this path.
import type { RootContent } from "mdast";
import { gfmToMarkdown } from "mdast-util-gfm";
import { toMarkdown } from "mdast-util-to-markdown";

import { markdownLink } from "../lib/routes.ts";

// Every `.md.ts` route (pages, schema, command, api) funnels its whole tree
// through this one function before it becomes text, regardless of whether a
// link came from hand-written MDX prose, the `<Link>` component, or a
// registry list (EndpointGroupList, CommandGroupList, SchemaList, ...). That
// makes it the single choke point to rewrite cross-page links to their
// markdown variant — see `markdownLink`.
function rewriteLinks(nodes: RootContent[]): void {
  for (const node of nodes) {
    if (node.type === "link") {
      node.url = markdownLink(node.url);
    }
    if ("children" in node && Array.isArray(node.children)) {
      rewriteLinks(node.children as RootContent[]);
    }
  }
}

// Source content (curl output, response headers, ...) often carries a
// trailing newline that reads fine in a fenced HTML `<pre>` but shows up as
// a stray blank line inside the fence in the markdown output. Trim it here,
// at serialization time, rather than in the content itself.
function trimCodeBlocks(nodes: RootContent[]): void {
  for (const node of nodes) {
    if (node.type === "code") {
      node.value = node.value.replace(/\n+$/, "");
    }
    if ("children" in node && Array.isArray(node.children)) {
      trimCodeBlocks(node.children as RootContent[]);
    }
  }
}

export function serialize(children: RootContent[]): string {
  rewriteLinks(children);
  trimCodeBlocks(children);
  return toMarkdown(
    { type: "root", children },
    {
      extensions: [gfmToMarkdown()],
      bullet: "-",
      rule: "-",
      emphasis: "_",
      fences: true,
      listItemIndent: "one",
      resourceLink: false,
    },
  );
}
