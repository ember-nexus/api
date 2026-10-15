// Every HTML page has a `*.md` twin (see [...slug].md.ts et al.) meant for
// LLM/agent consumption, not search results — it's the same content as its
// HTML page, so it must never rank or get cached as a separate result.
// `tools/Caddyfile` already enforces this for the production static build via
// `Link: rel="canonical"` + `X-Robots-Tag: noindex` response headers, but that
// only covers Caddy's `file_server` — the plain `astro dev` server (no
// adapter, `output: 'static'`) never goes through Caddy, so it served `*.md`
// with neither header. This integration mirrors the same two headers for dev,
// same reasoning as `trailingSlashRedirect.ts` next to it.
import type { AstroIntegration } from "astro";

export default function mdRobotsHeaders(): AstroIntegration {
  return {
    name: "md-robots-headers",
    hooks: {
      "astro:server:setup": ({ server }): void => {
        server.middlewares.use((req, res, next) => {
          const url = new URL(req.url ?? "/", "http://localhost");
          const match = /^(.+)\.md$/.exec(url.pathname);
          if (!match) {
            next();
            return;
          }
          // "/index.md" is the markdown twin of "/", not "/index" — mirrors
          // the same exception in tools/Caddyfile.
          const canonical = match[1] === "/index" ? "/" : match[1];
          res.setHeader("Link", `<${canonical}>; rel="canonical"`);
          res.setHeader("X-Robots-Tag", "noindex, nofollow, noarchive");
          next();
        });
      },
    },
  };
}
