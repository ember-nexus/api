// Markdown counterpart of `api/[endpoint].astro`. Endpoint, not a page — see
// the routing note in `src/pages/[...slug].md.ts`.
import SwaggerParser from "@apidevtools/swagger-parser";
import type { APIRoute, GetStaticPaths } from "astro";

import { endpointParam, endpointPath, prime } from "../../lib/index.ts";
import {
  requestBodyCard,
  requestCard,
  requestHeaderCard,
  requestParameterCard,
  responseCard,
  responseHeaderCard,
  responseSchemaCard,
} from "../../mdmx/cards/index.ts";
import { footerNav } from "../../mdmx/footerNav.ts";
import { headerMeta } from "../../mdmx/headerMeta.ts";
import { getCollection, renderMd } from "../../mdmx/index.ts";
import { serialize } from "../../mdmx/serialize.ts";
import {
  extractHarExamples,
  extractRequestBody,
  extractRequestHeaders,
  extractRequestParameters,
  extractResponseExamples,
  extractResponseHeaders,
  extractSchema,
} from "../../util/index.ts";

export const getStaticPaths: GetStaticPaths = async () => {
  const entries = await getCollection("endpoints");
  return entries.map((entry) => ({
    params: { endpoint: endpointParam(entry.data.endpoint) },
    props: { entry },
  }));
};

export const GET: APIRoute = async ({ props: { entry } }) => {
  await prime();
  const { method, endpointUrl, swaggerUrl, name } = entry.data;

  const spec = await SwaggerParser.dereference("./src/data/swagger.json");
  // Undereferenced twin of `spec`, used only to tell a response schema that
  // is a bare `$ref` to a named `components.schemas` entry (e.g. `Problem`)
  // apart from an inline/anonymous one — see `schemaRefName` in
  // `extractResponseExamples`.
  const rawSpec = await SwaggerParser.parse("./src/data/swagger.json");
  const path = swaggerUrl ?? "/";
  const specMethod = swaggerUrl === undefined ? "get" : method;

  const requests = extractHarExamples(spec, path, specMethod);
  const requestParameters = extractRequestParameters(spec, path, specMethod);
  const requestHeaders = extractRequestHeaders(spec, path, specMethod);
  const requestBody = extractRequestBody(spec, path, specMethod);
  const responseHeaders = extractResponseHeaders(spec, path, specMethod);
  const responseExamples = extractResponseExamples(
    spec,
    path,
    specMethod,
    rawSpec,
  );

  // A named response schema is only worth pulling out into its own
  // "Response Schema" section when it actually repeats across this page's
  // *error* responses (e.g. `Problem` on every 4xx/5xx) — a schema used by
  // a single response stays inline, right where it's read. 2xx/3xx
  // responses are excluded entirely: they're few, and represent the
  // intended behavior, so `responseCard` always keeps them fully inline.
  const schemaRefCounts = new Map<string, number>();
  for (const e of responseExamples) {
    if (e.httpStatusCode >= 400 && e.schemaRefName !== null) {
      schemaRefCounts.set(
        e.schemaRefName,
        (schemaRefCounts.get(e.schemaRefName) ?? 0) + 1,
      );
    }
  }
  const dedupedSchemaNames = new Set(
    [...schemaRefCounts.entries()]
      .filter(([, count]) => count >= 2)
      .map(([name]) => name),
  );
  const dedupedSchemas = [...dedupedSchemaNames]
    .map((name) => extractSchema(spec, name))
    .filter((s) => s !== undefined);

  const body = await renderMd(entry);

  const md = serialize([
    ...headerMeta(),
    {
      type: "heading",
      depth: 1,
      children: [
        {
          type: "text",
          value: `${method.toUpperCase()} ${endpointUrl} — ${name}`,
        },
      ],
    },
    ...body,
    ...requestCard(requests),
    ...responseCard(responseExamples, dedupedSchemaNames),
    ...requestParameterCard(requestParameters),
    ...requestHeaderCard(requestHeaders),
    ...(requestBody ? requestBodyCard(requestBody) : []),
    ...responseSchemaCard(dedupedSchemas),
    ...responseHeaderCard(responseHeaders),
    ...(await footerNav(endpointPath(entry.data.endpoint))),
  ]);

  return new Response(md, {
    headers: { "Content-Type": "text/markdown; charset=utf-8" },
  });
};
