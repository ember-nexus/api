import type { RootContent } from "mdast";

import type { RequestBody } from "../../type";
import { parseMarkdownSource } from "../markdownSource.ts";

export function requestBodyCard(body: RequestBody): RootContent[] {
  if (body.contents.length === 0) return [];

  const multiple = body.contents.length > 1;
  const nodes: RootContent[] = [
    {
      type: "heading",
      depth: 2,
      children: [
        {
          type: "text",
          value: `Request Body (${body.required ? "required" : "optional"})`,
        },
      ],
    },
    ...parseMarkdownSource(body.description),
  ];

  for (const c of body.contents) {
    if (multiple) {
      nodes.push({
        type: "heading",
        depth: 3,
        children: [{ type: "inlineCode", value: c.mimeType }],
      });
    }
    if (c.example !== null) {
      nodes.push({
        type: "heading",
        depth: 4,
        children: [{ type: "text", value: "Example" }],
      });
      nodes.push({
        type: "code",
        lang: c.example.type,
        value: c.example.content,
      });
    }
    if (c.schema !== null) {
      nodes.push({
        type: "heading",
        depth: 4,
        children: [{ type: "text", value: "Schema" }],
      });
      nodes.push({ type: "code", lang: "json", value: c.schema });
    }
  }

  return nodes;
}
