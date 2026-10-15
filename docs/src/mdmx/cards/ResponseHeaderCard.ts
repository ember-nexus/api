import type { RootContent } from "mdast";

import { linksParagraph } from "./linkNode.ts";
import type { ResponseHeader } from "../../type";
import { parseMarkdownSource } from "../markdownSource.ts";

function headerSection(h: ResponseHeader, depth: 3 | 4): RootContent[] {
  const nodes: RootContent[] = [
    {
      type: "heading",
      depth,
      children: [
        { type: "inlineCode", value: h.header },
        { type: "text", value: ` (${h.presence})` },
      ],
    },
    ...parseMarkdownSource(h.description),
  ];
  if (h.example) {
    nodes.push({
      type: "paragraph",
      children: [
        { type: "text", value: "Example: " },
        { type: "inlineCode", value: h.example },
      ],
    });
  }
  const links = linksParagraph(h.links);
  if (links) nodes.push(links);
  return nodes;
}

export function responseHeaderCard(headers: ResponseHeader[]): RootContent[] {
  const important = headers.filter((h) => h.important);
  const others = headers.filter((h) => !h.important);

  const nodes: RootContent[] = [
    {
      type: "heading",
      depth: 2,
      children: [{ type: "text", value: "Response Header" }],
    },
    ...important.flatMap((h) => headerSection(h, 3)),
  ];

  if (others.length > 0) {
    nodes.push({
      type: "heading",
      depth: 3,
      children: [{ type: "text", value: "Other Headers" }],
    });
    nodes.push(...others.flatMap((h) => headerSection(h, 4)));
  }

  return nodes;
}
