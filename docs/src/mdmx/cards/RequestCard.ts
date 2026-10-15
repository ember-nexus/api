import { HTTPSnippet, type TargetId } from "httpsnippet";
import type { RootContent } from "mdast";

import type { RequestExample } from "../../type";
import { curlfmt } from "../../util/curl-fmt.ts";

// `RequestCard.astro` renders four language tabs (curl, Python, JS, PHP) for
// human readers comparing client libraries. The markdown target is read by
// LLMs, not browsed tab-by-tab — curl alone says everything an agent needs
// to reproduce the request, so the other three are skipped here. When an
// endpoint has more than one request example (e.g. direct vs. resumable file
// upload), all of them are printed as curl — they're semantically distinct
// requests, unlike the four language variants of a single example.
export function requestCard(requests: RequestExample[]): RootContent[] {
  const nodes: RootContent[] = [
    {
      type: "heading",
      depth: 2,
      children: [{ type: "text", value: "Request" }],
    },
  ];

  const showTitles = requests.length > 1;

  requests.forEach((request, i) => {
    if (showTitles) {
      nodes.push({
        type: "heading",
        depth: 3,
        children: [{ type: "text", value: request.name ?? `Example ${i + 1}` }],
      });
    }

    const snippet = new HTTPSnippet(request.har);
    const opts = { indent: "\t" };
    const raw = snippet.convert(
      "shell" as unknown as TargetId,
      "curl",
      opts,
    ) as string;
    const example = curlfmt(raw, {
      headerPriority: ["Authorization", "Content-Type", "Accept"],
    });

    nodes.push({ type: "code", lang: "bash", value: example });
  });

  return nodes;
}
