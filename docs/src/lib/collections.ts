// Synchronous access to the collection/swagger data the group-list
// components (`EndpointGroupList`, `CommandGroupList`, `SchemaList`) query.
//
// The HTML versions of those components can `await getCollection(...)`
// directly in their Astro frontmatter. The markdown render target can't:
// `jsx()` in `src/mdmx/jsx-runtime.ts` is synchronous on purpose (making it
// async would infect the whole mdast tree with promises), but its markdown
// component implementations still need this same data. `prime()` fetches
// everything once up front; the getters below then read from a
// module-level cache.
//
// The `.astro` components have been refactored to read from these same
// getters too, so the group/sort/filter logic behind both render targets
// cannot drift apart.
import SwaggerParser from "@apidevtools/swagger-parser";
import { type CollectionEntry, getCollection } from "astro:content";
import type { OpenAPIObject } from "openapi3-ts/oas31";

import type { Schema } from "../type";
import { extractSchemas } from "../util/index.ts";

let endpoints: CollectionEntry<"endpoints">[] = [];
let commands: CollectionEntry<"commands">[] = [];
let schemas: Schema[] = [];

// Cache the in-flight *promise*, not just a "done" flag — assigning it
// synchronously (before the first `await`) means concurrent callers (every
// page calls `prime()`) reuse the same fetch instead of each racing to see
// a still-unprimed cache and redundantly redoing the work. `primed` tracks
// actual *completion* separately, so `assertPrimed()` still catches a caller
// that forgot to `await prime()` (as opposed to one that merely called it).
let primePromise: Promise<void> | null = null;
let primed = false;

/** Populates the module-level cache. Idempotent — safe to call from every page. */
export function prime(): Promise<void> {
  if (!primePromise) {
    primePromise = Promise.all([
      getCollection("endpoints"),
      getCollection("commands"),
      SwaggerParser.parse("./src/data/swagger.json") as Promise<OpenAPIObject>,
    ]).then(([endpointEntries, commandEntries, spec]) => {
      endpoints = endpointEntries;
      commands = commandEntries;
      schemas = extractSchemas(spec);
      primed = true;
      return;
    });
  }
  return primePromise;
}

function assertPrimed(): void {
  if (!primed)
    throw new Error(
      "[collections] prime() must be awaited before calling a getter",
    );
}

/** "config:set" -> "config", "healthcheck" -> "system" (no namespace = "system"). */
export const commandNamespace = (command: string): string => {
  const colonIdx = command.indexOf(":");
  return colonIdx === -1 ? "system" : command.slice(0, colonIdx);
};

export function endpointsInGroup(
  group: string,
): CollectionEntry<"endpoints">[] {
  assertPrimed();
  return endpoints
    .filter((e) => e.data.group === group)
    .sort((a, b) => a.id.localeCompare(b.id));
}

export function commandsInGroup(group: string): CollectionEntry<"commands">[] {
  assertPrimed();
  return commands
    .filter((e) => commandNamespace(e.data.command) === group)
    .sort((a, b) => a.data.command.localeCompare(b.data.command));
}

export function allSchemas(): Schema[] {
  assertPrimed();
  return [...schemas].sort((a, b) => a.name.localeCompare(b.name));
}
