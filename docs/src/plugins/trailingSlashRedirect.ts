// Every route's canonical URL is slash-free (see `lib/routes.ts`'s `pageRouteParam`
// / `pagePath` and friends, and the `[...slug].astro` route they feed). Astro's
// own `trailingSlash: 'never'` config looked like the built-in way to enforce
// that in dev, but for the plain `astro dev` server (no adapter — this project
// is `output: 'static'`) it doesn't redirect a mismatched request, it 404s it
// (`vite-plugin-astro-server/trailing-slash.js`'s `evaluateTrailingSlash`, action
// "reject"). A 404 for `/foo/` is worse than today's status quo — both `/foo` and
// `/foo/` quietly serving the same page — so this integration redirects instead,
// left registered with `trailingSlash` at its default ('ignore') so Astro's own
// dev middleware never rejects the request out from under us.
//
// The production build has no dev server at all; the equivalent redirect for the
// static output lives in `tools/Caddyfile`.
import type { AstroIntegration } from "astro";

export default function trailingSlashRedirect(): AstroIntegration {
  return {
    name: "trailing-slash-redirect",
    hooks: {
      "astro:server:setup": ({ server }): void => {
        server.middlewares.use((req, res, next) => {
          const url = new URL(req.url ?? "/", "http://localhost");
          if (url.pathname === "/" || !url.pathname.endsWith("/")) {
            next();
            return;
          }
          res.statusCode = 301;
          res.setHeader("Location", url.pathname.slice(0, -1) + url.search);
          res.end();
        });
      },
    },
  };
}
