// /llms.txt — https://llmstxt.org/
//
// A static header (who we are, how to fetch a plain-text copy of any page)
// followed by a dynamic body: the same nested tree `buildSidebar()` produces
// for the HTML sidebar, rendered as nested markdown lists, so it can't
// silently drift out of sync with the actual site nav.
import type { APIRoute } from "astro";

import { apiVersion, buildLlmsBody } from "../lib/index.ts";
import { serialize } from "../mdmx/serialize.ts";

const staticHeader = `# Ember Nexus API

> Ember Nexus is a self-hosted, source-first REST API for connected data: elements (nodes) and
> the relations between them, with user accounts, permissions, file storage and search built in.

This site's documentation is also available as plain Markdown: append \`.md\` to any page's URL
(the homepage is available at [/index.md](/index.md)).

## Links

- [Homepage](/): Documentation home
- [OpenAPI / Swagger spec](/swagger.json): Full API specification
- [GitHub](https://github.com/ember-nexus/api): Source code
- [Docker Hub](https://hub.docker.com/r/embernexus/api): Container image
- [Discord](https://discord.gg/qbQFBrJrRC): Community chat`;

export const GET: APIRoute = async () => {
  const dynamicBody = serialize(await buildLlmsBody());

  const body = `${staticHeader}\n\nAPI version: ${apiVersion()}\n\n${dynamicBody}`;

  return new Response(body, {
    headers: { "Content-Type": "text/markdown; charset=utf-8" },
  });
};
