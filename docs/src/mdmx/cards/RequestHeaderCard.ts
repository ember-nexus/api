import type { RootContent } from "mdast";

import { linksParagraph } from "./linkNode.ts";
import type { RequestHeader } from "../../type";
import { parseMarkdownSource } from "../markdownSource.ts";

export function requestHeaderCard(headers: RequestHeader[]): RootContent[] {
  const nodes: RootContent[] = [
    {
      type: "heading",
      depth: 2,
      children: [{ type: "text", value: "Request Header" }],
    },
  ];
  for (const h of headers) {
    nodes.push({
      type: "heading",
      depth: 3,
      children: [
        { type: "inlineCode", value: h.header },
        { type: "text", value: ` (${h.presence})` },
      ],
    });
    nodes.push(...parseMarkdownSource(h.description));
    const links = linksParagraph(h.links);
    if (links) nodes.push(links);
  }
  return nodes;
}
