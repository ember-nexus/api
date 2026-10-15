// Markdown counterpart of `[...slug].astro`. Must be an *endpoint*
// (`GET`/`getStaticPaths`), not a `.md.astro` page: in Astro 7.2.4,
// `getOutFile` emits `<pathname>/index.html` unconditionally for
// `routeType: 'page'` under `build.format: 'directory'`, which would turn
// `foo.md` into `foo.md/index.html`. Only `routeType: 'endpoint'` writes
// `basename(pathname)` verbatim — see task.md §9.3.
import type { APIRoute, GetStaticPaths } from "astro";

import { pagePath, pageRouteParamMd, prime } from "../lib/index.ts";
import { footerNav } from "../mdmx/footerNav.ts";
import { headerMeta } from "../mdmx/headerMeta.ts";
import { getCollection, renderMd } from "../mdmx/index.ts";
import { serialize } from "../mdmx/serialize.ts";

export const getStaticPaths: GetStaticPaths = async () => {
  const pages = await getCollection("pages");
  return pages.map((entry) => ({
    params: { slug: pageRouteParamMd(entry.id) },
    props: { entry },
  }));
};

export const GET: APIRoute = async ({ props: { entry } }) => {
  await prime();
  const body = await renderMd(entry);
  // The "index" entry's HTML route is "/", not "/index" — see the routing
  // note in `lib/sidebar.ts`'s pagesSection().
  const htmlPath = entry.id === "index" ? "/" : pagePath(entry.id);
  const md = serialize([
    ...headerMeta(),
    {
      type: "heading",
      depth: 1,
      children: [{ type: "text", value: entry.data.title }],
    },
    ...body,
    ...(await footerNav(htmlPath)),
  ]);
  return new Response(md, {
    headers: { "Content-Type": "text/markdown; charset=utf-8" },
  });
};
