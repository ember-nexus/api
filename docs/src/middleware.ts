// `astro dev`'s built-in "no route matched" fallback always serves
// `404.astro`'s HTML, regardless of the requested extension — every
// unresolved `*.md` request gets HTML back instead of its markdown 404 twin.
//
// This only needs fixing here: this site's production build is fully
// static, so Astro middleware (like this one) never runs there at all —
// Caddy's `handle_errors` block does the equivalent split for the built
// site (see tools/Caddyfile, 404.md.ts).
import { defineMiddleware } from "astro:middleware";

import { render404Md } from "./pages/404.md.ts";

export const onRequest = defineMiddleware(async (context, next) => {
  const response = await next();
  if (response.status === 404 && context.url.pathname.endsWith(".md")) {
    return new Response(await render404Md(), {
      status: 404,
      headers: { "Content-Type": "text/markdown; charset=utf-8" },
    });
  }
  return response;
});
