// Markdown counterpart of `404.astro`. Caddy's `handle_errors` rewrites any
// unresolved `*.md` request here (see tools/Caddyfile) the same way it
// rewrites unresolved HTML requests to `404.html`. `render404Md` is also
// reused by `../middleware.ts` for the same fallback in `astro dev`, which
// has no Caddy in front of it.
import type { APIRoute } from "astro";

import { footerNav } from "../mdmx/footerNav.ts";
import { serialize } from "../mdmx/serialize.ts";

export async function render404Md(): Promise<string> {
  return serialize([
    {
      type: "heading",
      depth: 1,
      children: [{ type: "text", value: "404 not found" }],
    },
    ...(await footerNav("/404")),
  ]);
}

export const GET: APIRoute = async () => {
  const md = await render404Md();
  return new Response(md, {
    headers: { "Content-Type": "text/markdown; charset=utf-8" },
  });
};
