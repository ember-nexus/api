import type { RootContent } from "mdast";

import { truncateJson } from "../truncateJson.ts";

interface Props {
  responseBody: object;
  responseHeaders: string;
}

/**
 * No interactive equivalent exists for the graph/table tabs — emit the raw
 * response instead of stripping it, same principle as the rest of this
 * registry: never silently drop content. Real search responses can run to
 * hundreds of KB (mixed node/relationship shapes, long text fields), so the
 * body goes through `truncateJson` first: small responses are untouched,
 * large ones keep one representative of every distinct shape plus capped
 * leaf strings, so the *structure* stays complete even when the *data*
 * doesn't. The `debug` step in this component's HTML counterpart likewise
 * isn't dropped — the LLM reading this page may itself be debugging a
 * search request.
 */
export function SearchResponseCard(props: Props): RootContent[] {
  return [
    {
      type: "heading",
      depth: 5,
      children: [{ type: "text", value: "Response Body" }],
    },
    {
      type: "code",
      lang: "json",
      value: JSON.stringify(truncateJson(props.responseBody), null, 2),
    },
    {
      type: "heading",
      depth: 5,
      children: [{ type: "text", value: "Response Headers" }],
    },
    { type: "code", lang: "http", value: props.responseHeaders },
  ];
}
