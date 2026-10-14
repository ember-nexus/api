import type { RootContent } from "mdast";

import type { Link as LinkType } from "../../type";

interface Props {
  link: LinkType;
  download?: boolean;
}

export function Link(props: Props): RootContent {
  return {
    type: "link",
    url: props.link.url,
    title: null,
    children: [{ type: "text", value: props.link.name }],
  };
}
