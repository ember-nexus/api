import type { RootContent } from "mdast";

import type { Schema } from "../../type";
import { parseMarkdownSource } from "../markdownSource.ts";

/**
 * Appendix listing the named schemas that `responseCard` deduped out of its
 * per-response `Schema` sections (e.g. `Problem`, referenced by every error
 * response) — printed once here instead of once per response. Only ever
 * called with schemas that actually repeat; see `pages/api/[endpoint].md.ts`.
 */
export function responseSchemaCard(schemas: Schema[]): RootContent[] {
  if (schemas.length === 0) {
    return [];
  }

  const nodes: RootContent[] = [
    {
      type: "heading",
      depth: 2,
      children: [{ type: "text", value: "Response Schema" }],
    },
  ];

  for (const s of schemas) {
    nodes.push({
      type: "heading",
      depth: 3,
      children: [{ type: "inlineCode", value: s.name }],
    });
    const description =
      typeof (s.schema as { description?: unknown } | undefined)
        ?.description === "string"
        ? (s.schema as { description: string }).description
        : null;
    if (description) {
      nodes.push(...parseMarkdownSource(description));
    }
    nodes.push({
      type: "code",
      lang: "json",
      value: JSON.stringify(s.schema, null, 2),
    });
  }

  return nodes;
}
