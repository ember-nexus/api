/**
 * Static OG-image route: /open-graph/<slug>.png
 *
 * One image is generated per page, endpoint, command, and schema.
 * The slug mirrors the public URL so the layout can derive the image path
 * from Astro.url.pathname without any extra lookup:
 *
 *   page   /getting-started/installation  → /open-graph/getting-started/installation.png
 *   api    /api/users_{uuid}              → /open-graph/api/users_{uuid}.png
 *   cmd    /command/backup-create         → /open-graph/command/backup-create.png
 *   schema /openapi-schema/element-id     → /open-graph/openapi-schema/element-id.png
 */

import SwaggerParser from "@apidevtools/swagger-parser";
import type { APIRoute, GetStaticPaths, GetStaticPathsItem } from "astro";
import { getCollection } from "astro:content";
import type { OpenAPIObject } from "openapi3-ts/oas31";

import { commandParam, endpointParam, pageSlug } from "../../lib/index.ts";
import { extractSchemas } from "../../util/index.ts";
import { generateOgImage } from "../../util/openGraph.ts";

export const getStaticPaths: GetStaticPaths = async () => {
  const result: GetStaticPathsItem[] = [];

  // ── pages collection ─────────────────────────────────────────────────────
  const pages = await getCollection("pages");
  for (const entry of pages) {
    const slug = entry.id === "index" ? "index" : pageSlug(entry.id);
    result.push({
      params: { slug },
      props: { title: entry.data.title },
    });
  }

  // ── endpoints collection ─────────────────────────────────────────────────
  const endpoints = await getCollection("endpoints");
  for (const entry of endpoints) {
    result.push({
      params: { slug: `api/${endpointParam(entry.data.endpoint)}` },
      props: { title: `${entry.data.name} endpoint` },
    });
  }

  // ── commands collection ──────────────────────────────────────────────────
  const commands = await getCollection("commands");
  for (const entry of commands) {
    const name = entry.data.name ?? entry.data.command;
    result.push({
      params: { slug: `command/${commandParam(entry.data.command)}` },
      props: { title: `${name} command` },
    });
  }

  // ── schemas (derived from swagger.json) ──────────────────────────────────
  const spec = (await SwaggerParser.parse(
    "./src/data/swagger.json",
  )) as OpenAPIObject;
  for (const schema of extractSchemas(spec)) {
    result.push({
      params: { slug: `openapi-schema/${schema.id}` },
      props: { title: `${schema.name} schema` },
    });
  }

  return result;
};

export const GET: APIRoute<{ title: string }> = async ({ props }) => {
  const buffer = await generateOgImage(props.title);
  return new Response(buffer, {
    status: 200,
    headers: { "Content-Type": "image/png" },
  });
};
