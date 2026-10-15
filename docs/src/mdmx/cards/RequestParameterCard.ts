import type { RootContent } from "mdast";

import { linksParagraph } from "./linkNode.ts";
import type { RequestParameter } from "../../type";
import { parseMarkdownSource } from "../markdownSource.ts";

// Same sort as `RequestParameterCard.astro`: by location, then name.
const LOCATION_ORDER: Record<RequestParameter["location"], number> = {
  path: 0,
  query: 1,
  cookie: 2,
};

function metaChips(param: RequestParameter): string[] {
  const chips = [param.location, param.type, param.format].filter(
    (v): v is string => v !== null,
  );
  return [...chips, ...param.constraints];
}

export function requestParameterCard(
  parameters: RequestParameter[],
): RootContent[] {
  if (parameters.length === 0) return [];

  const sorted = [...parameters].sort(
    (a, b) =>
      LOCATION_ORDER[a.location] - LOCATION_ORDER[b.location] ||
      a.name.localeCompare(b.name),
  );

  const nodes: RootContent[] = [
    {
      type: "heading",
      depth: 2,
      children: [{ type: "text", value: "Request Parameters" }],
    },
  ];

  for (const param of sorted) {
    nodes.push({
      type: "heading",
      depth: 3,
      children: [
        { type: "inlineCode", value: param.name },
        { type: "text", value: ` (${param.presence})` },
      ],
    });
    nodes.push({
      type: "paragraph",
      children: [{ type: "inlineCode", value: metaChips(param).join(" · ") }],
    });
    nodes.push(...parseMarkdownSource(param.description));
    if (param.example !== null) {
      nodes.push({
        type: "paragraph",
        children: [
          { type: "text", value: "Example: " },
          { type: "inlineCode", value: param.example },
        ],
      });
    }
    const links = linksParagraph(param.links);
    if (links) nodes.push(links);
  }

  return nodes;
}
