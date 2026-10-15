// Appended to every `.md.ts` route's output. An LLM fetching a single page
// has none of the surrounding chrome (header, sidebar) a human browser gets —
// this is the markdown-only substitute: how to get back to the homepage or
// the full site map, and where the adjacent pages are.
//
// Prev/next reuses the exact same sidebar-derived ordering `PageNav.astro`
// renders on the HTML page, so the two can't drift apart — see `lib/pageNav`.
import type { RootContent } from "mdast";

import { type NavItem, buildNavItems, findPrevNext } from "../lib/pageNav.ts";
import { markdownPath } from "../lib/routes.ts";

const navLink = (label: string, name: string, href: string): RootContent => ({
  type: "paragraph",
  children: [
    { type: "text", value: `${label}: ` },
    {
      type: "link",
      url: href,
      title: null,
      children: [{ type: "text", value: name }],
    },
  ],
});

// HTML page paths (from the sidebar / `NavItem.url`) need `markdownPath` to
// point at their `.md` twin; `/llms.txt` is already the final target and
// must NOT go through it (it has no HTML twin to derive from).
const pageLink = (label: string, name: string, pagePath: string): RootContent =>
  navLink(label, name, markdownPath(pagePath));

export async function footerNav(currentPath: string): Promise<RootContent[]> {
  const { prev, next }: { prev?: NavItem; next?: NavItem } = findPrevNext(
    await buildNavItems(),
    currentPath,
  );

  const nodes: RootContent[] = [
    { type: "thematicBreak" },
    pageLink("Home", "Ember Nexus API docs", "/"),
    navLink("Full site map", "llms.txt", "/llms.txt"),
  ];

  if (prev) nodes.push(pageLink("Previous", prev.name, prev.url));
  if (next) nodes.push(pageLink("Next", next.name, next.url));

  return nodes;
}
