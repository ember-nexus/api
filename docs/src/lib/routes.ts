// Single source of truth for how a collection entry maps to a URL.
//
// Both the page routes (getStaticPaths) and the sidebar import from here, so a
// link in the sidebar and the route it points at can never drift out of sync.
//
// Convention: `*Param` returns the bare route param; `*Path` returns the full
// absolute href. Routes use the param helpers, the sidebar uses the path helpers.

/**
 * Strip a leading ordering prefix like "01-" or "1_" from a path segment.
 * This lets you order files lexically on disk (01-intro.md, 02-auth.md…) without
 * the numbers leaking into URLs or labels.
 *
 * If you do NOT prefix your filenames, this is a no-op and safe to keep.
 * If you have a legitimate page like "2024-report.md", drop this call.
 */
export const cleanSegment = (segment: string): string =>
  segment.replace(/^\d+[-_]/, "");

/** "getting-started" -> "Getting started" (used for folder/group headings). */
export const humanize = (segment: string): string => {
  const s = cleanSegment(segment).replace(/[-_]/g, " ").trim();
  return s.charAt(0).toUpperCase() + s.slice(1);
};

// --- pages: routed by [...slug], slug === the (cleaned) collection id ----------

/** "guides/01-auth" -> "guides/auth" */
export const pageSlug = (id: string): string =>
  id.split("/").map(cleanSegment).join("/");

/** "guides/01-auth" -> "/guides/auth" */
export const pagePath = (id: string): string => "/" + pageSlug(id);

// Both `[...slug].astro` (HTML) and `[...slug].md.ts` (markdown) build their
// `getStaticPaths()` param from the same `pages` collection entry, and both
// need to special-case the "index" entry — but not the same way. The HTML
// route wants `undefined` so [...slug] claims the bare "/" route; the
// markdown route is an *endpoint*, not a page, so it needs the literal
// string "index" or `basename()` on an empty pathname resolves to "".
// Keeping the cleaning logic (`pageSlug`) in one shared place means the two
// route sets can't quietly drift apart.

/** Route param for the HTML page shell. */
export const pageRouteParam = (id: string): string | undefined =>
  id === "index" ? undefined : pageSlug(id);

/** Route param for the markdown endpoint. */
export const pageRouteParamMd = (id: string): string =>
  id === "index" ? "index" : pageSlug(id);

/** "guides/01-auth" -> "/guides/auth.md", "index" -> "/index.md" */
export const pagePathMd = (id: string): string =>
  "/" + pageRouteParamMd(id) + ".md";

/**
 * Generic HTML path -> markdown path mapping, used to link from *any*
 * currently-rendered page (pages, schemas, commands, endpoints) to its
 * markdown twin without knowing which collection produced it.
 *
 * Mirrors `pageRouteParamMd`'s index special-case: the root "/" has no
 * basename to append ".md" to (and `foo.md/index.html` is not a valid
 * static file — see the routing note in `[...slug].md.ts`), so it maps to
 * "/index.md" instead of "/.md". Every other path just gets ".md" appended
 * after trimming a trailing slash.
 */
export const markdownPath = (pathname: string): string => {
  const trimmed = pathname.replace(/\/$/, "");
  return trimmed === "" ? "/index.md" : `${trimmed}.md`;
};

/**
 * Same idea as `markdownPath`, but for a link `href` found inside generated
 * markdown content rather than the current page's own pathname — used by
 * `serialize()` to rewrite every cross-page link in the `.md` output so it
 * points at the linked page's markdown variant too, instead of dead-ending
 * back into an HTML page.
 *
 * Leaves untouched: external links (no leading "/"), protocol-relative links
 * ("//..."), and asset links — anything whose last path segment already has
 * a file extension, e.g. "/swagger.json", "/logo.svg". A "#hash" suffix is
 * preserved across the rewrite.
 */
export const markdownLink = (href: string): string => {
  if (!href.startsWith("/") || href.startsWith("//")) return href;

  const hashIndex = href.indexOf("#");
  const path = hashIndex === -1 ? href : href.slice(0, hashIndex);
  const hash = hashIndex === -1 ? "" : href.slice(hashIndex);

  const lastSegment = path.split("/").pop() ?? "";
  if (lastSegment.includes(".")) return href; // asset, not a page

  return markdownPath(path) + hash;
};

// --- commands: routed by command/[command] ------------------------------------

/** "config:set" -> "config-set" */
export const commandParam = (command: string): string =>
  command.replace(/:/g, "-");

/** "config:set" -> "/command/config-set" */
export const commandPath = (command: string): string =>
  "/command/" + commandParam(command);

// --- endpoints: routed by api/[endpoint] (group is NOT in the URL) ------------

/** "users/{uuid}" -> "users_{uuid}" */
export const endpointParam = (endpoint: string): string =>
  endpoint.replace(/\//g, "_");

/** "users/{uuid}" -> "/api/users_{uuid}" */
export const endpointPath = (endpoint: string): string =>
  "/api/" + endpointParam(endpoint);

// --- OpenAPI schemas: routed by openapi-schema/[schema] ------------------------

/** "ElementId" -> "element-id" (the id produced by extractSchemas / schemaParam) */
export const schemaPath = (id: string): string => "/openapi-schema/" + id;
