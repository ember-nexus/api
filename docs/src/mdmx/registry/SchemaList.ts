import type { RootContent } from "mdast";

import { allSchemas } from "../../lib/collections.ts";
import { schemaPath } from "../../lib/routes.ts";

/** Same query as the HTML `SchemaList.astro` — see `src/lib/collections.ts`. */
export function SchemaList(): RootContent {
  const schemas = allSchemas();
  return {
    type: "list",
    ordered: false,
    start: null,
    children: schemas.map((s): RootContent => ({
      type: "listItem",
      checked: null,
      spread: false,
      children: [
        {
          type: "paragraph",
          children: [
            {
              type: "link",
              url: schemaPath(s.id),
              title: null,
              children: [{ type: "inlineCode", value: s.name }],
            },
          ],
        },
      ],
    })),
  };
}
