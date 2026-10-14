import type { RootContent } from "mdast";

import { endpointsInGroup } from "../../lib/collections.ts";
import { endpointPath } from "../../lib/routes.ts";

interface Props {
  group: string;
}

/** Same query as the HTML `EndpointGroupList.astro` — see `src/lib/collections.ts`. */
export function EndpointGroupList(props: Props): RootContent {
  const entries = endpointsInGroup(props.group);
  return {
    type: "list",
    ordered: false,
    start: null,
    children: entries.map((e): RootContent => ({
      type: "listItem",
      checked: null,
      spread: false,
      children: [
        {
          type: "paragraph",
          children: [
            {
              type: "link",
              url: endpointPath(e.data.endpoint),
              title: null,
              children: [
                {
                  type: "text",
                  value: `${e.data.method.toUpperCase()} ${e.data.endpointUrl} — ${e.data.name}`,
                },
              ],
            },
          ],
        },
      ],
    })),
  };
}
