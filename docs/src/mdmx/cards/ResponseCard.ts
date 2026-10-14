import type { RootContent } from "mdast";

import { linksParagraph } from "./linkNode.ts";
import {
  type ResponseExample,
  getHttpStatusCodeLabel,
} from "../../type/index.ts";
import { parseMarkdownSource } from "../markdownSource.ts";

/**
 * `responseCard` renders a response's `Schema` section inline unless its
 * `schemaRefName` is in this set, in which case it points at the shared
 * entry in the `Response Schema` appendix (`responseSchemaCard`) instead.
 * Callers build this from whichever `schemaRefName`s repeat across the
 * page's responses — see `pages/api/[endpoint].md.ts`.
 *
 * Both this and the `Headers` compaction below apply to 4xx/5xx responses
 * only — 2xx/3xx responses are few, and represent the intended/successful
 * behavior, so they're kept fully inline and self-contained rather than
 * pointing elsewhere on (or off) the page.
 */
export function responseCard(
  examples: ResponseExample[],
  dedupedSchemaNames: ReadonlySet<string> = new Set(),
): RootContent[] {
  const nodes: RootContent[] = [
    {
      type: "heading",
      depth: 2,
      children: [{ type: "text", value: "Response" }],
    },
  ];

  for (const e of examples) {
    const label = e.name ?? getHttpStatusCodeLabel(e.httpStatusCode);
    nodes.push({
      type: "heading",
      depth: 3,
      children: [{ type: "text", value: `${e.httpStatusCode} · ${label}` }],
    });
    nodes.push(...parseMarkdownSource(e.description));

    if (e.body?.type === "binary") {
      nodes.push({
        type: "heading",
        depth: 4,
        children: [{ type: "text", value: "Body" }],
      });
      nodes.push({
        type: "paragraph",
        children: [
          {
            type: "emphasis",
            children: [{ type: "text", value: "binary response / raw file" }],
          },
        ],
      });
    } else if (e.body !== null && e.body.content) {
      nodes.push({
        type: "heading",
        depth: 4,
        children: [{ type: "text", value: "Body" }],
      });
      nodes.push({ type: "code", lang: e.body.type, value: e.body.content });
    }

    const isErrorResponse = e.httpStatusCode >= 400;

    if (e.headerNames.length > 0) {
      nodes.push({
        type: "heading",
        depth: 4,
        children: [{ type: "text", value: "Headers" }],
      });
      if (isErrorResponse) {
        const children: RootContent[] = [];
        e.headerNames.forEach((name, i) => {
          if (i > 0) children.push({ type: "text", value: ", " });
          children.push({ type: "inlineCode", value: name });
        });
        children.push({
          type: "text",
          value:
            ". Descriptions and example values are in the Response Header section below.",
        });
        nodes.push({ type: "paragraph", children });
      } else {
        nodes.push({ type: "code", lang: "http", value: e.headers });
      }
    }

    if (
      isErrorResponse &&
      e.schemaRefName !== null &&
      dedupedSchemaNames.has(e.schemaRefName)
    ) {
      nodes.push({
        type: "heading",
        depth: 4,
        children: [{ type: "text", value: "Schema" }],
      });
      nodes.push({
        type: "paragraph",
        children: [
          { type: "text", value: "Same as " },
          { type: "inlineCode", value: e.schemaRefName },
          {
            type: "text",
            value: " — see the Response Schema section below.",
          },
        ],
      });
    } else if (e.schema !== null) {
      nodes.push({
        type: "heading",
        depth: 4,
        children: [{ type: "text", value: "Schema" }],
      });
      nodes.push({ type: "code", lang: "json", value: e.schema });
    }

    const links = linksParagraph(e.links);
    if (links) nodes.push(links);
  }

  return nodes;
}
