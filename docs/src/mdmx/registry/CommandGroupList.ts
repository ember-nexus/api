import type { RootContent } from "mdast";

import { commandsInGroup } from "../../lib/collections.ts";
import { commandPath } from "../../lib/routes.ts";

interface Props {
  group: string;
}

/** Same query as the HTML `CommandGroupList.astro` — see `src/lib/collections.ts`. */
export function CommandGroupList(props: Props): RootContent {
  const entries = commandsInGroup(props.group);
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
              url: commandPath(e.data.command),
              title: null,
              children: [
                {
                  type: "inlineCode",
                  value: `php bin/console ${e.data.command}`,
                },
              ],
            },
          ],
        },
      ],
    })),
  };
}
