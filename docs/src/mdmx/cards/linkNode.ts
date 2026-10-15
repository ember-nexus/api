import type { RootContent } from "mdast";

import type { Link } from "../../type";

export function linkNode(link: Link): RootContent {
  return {
    type: "link",
    url: link.url,
    title: null,
    children: [{ type: "text", value: link.name }],
  };
}

/** A single paragraph of links, " · "-separated — same list `Link.astro` renders inline. */
export function linksParagraph(links: Link[]): RootContent | null {
  if (links.length === 0) return null;
  const children: RootContent[] = [];
  links.forEach((link, i) => {
    if (i > 0) children.push({ type: "text", value: " · " });
    children.push(linkNode(link));
  });
  return { type: "paragraph", children };
}
