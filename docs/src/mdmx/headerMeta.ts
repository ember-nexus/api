// Prepended to every `.md.ts` route's output, right before the page's `#`
// heading. The HTML sidebar shows the API version (see `Header.astro`), but
// nothing told an LLM reading a markdown page - or `llms.txt` - which API
// version the docs describe. See also `footerNav.ts`, its counterpart at the
// bottom of the page.
import type { RootContent } from "mdast";

import { apiVersion } from "../lib/version.ts";

export function headerMeta(): RootContent[] {
  return [
    {
      type: "paragraph",
      children: [{ type: "text", value: `API version: ${apiVersion()}` }],
    },
    { type: "thematicBreak" },
  ];
}
