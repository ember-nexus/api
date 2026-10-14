import SwaggerParser from "@apidevtools/swagger-parser";
import { getCollection } from "astro:content";
import type { OpenAPIObject } from "openapi3-ts/oas31";

import {
  commandPath,
  endpointPath,
  humanize,
  pagePath,
  schemaPath,
} from "./routes.ts";
import type {
  HttpMethod,
  SidebarEndpoint,
  SidebarGroup,
  SidebarItem,
  SidebarLink,
  SidebarLinkGroup,
} from "../type/Sidebar";
import { extractSchemas } from "../util/index.ts";

// Lexical order by collection id (the file path without extension) is THE
// ordering for the sidebar. We sort once up front; everything downstream just
// preserves first-seen order, so the on-disk filename order wins everywhere.
const byId = (a: { id: string }, b: { id: string }): number =>
  a.id.localeCompare(b.id);

/**
 * Internal entry type that carries both the original (full) id for URL
 * building and a shortened id (with the top-level section folder stripped)
 * for tree construction.
 */
interface InternalEntry {
  shortId: string; // path relative to the section folder, e.g. "01-installation.mdx"
  name: string; // display label
  url: string; // already-computed absolute href
}

/**
 * Build a nested tree from entries with ids of the form "a/b/c".
 *
 * Every path segment except the last may become either:
 *   - A SidebarGroup (variant "nested") if no page exists at that exact path, or
 *   - A SidebarLinkGroup if a page exists at the same path as a folder
 *     (e.g. "02-search" alongside "02-search/..." entries).
 *
 * Leaf nodes that are the "parent" of a link-group are absorbed into the
 * link-group node and not emitted separately.
 */
function buildTree(entries: InternalEntry[]): SidebarItem[] {
  const root: SidebarItem[] = [];

  // folder key -> items array for that folder
  const folderItems = new Map<string, SidebarItem[]>([["", root]]);

  // folder key -> the group/link-group node
  const folderNodes = new Map<string, SidebarGroup | SidebarLinkGroup>();

  // stem -> entry (for page+folder collision detection)
  const byStem = new Map<string, InternalEntry>();
  for (const entry of entries) {
    byStem.set(entry.shortId, entry);
  }

  // Pre-compute which stems are "folder parents" — i.e. entries whose shortId
  // is a prefix of another entry's path. We need this upfront so that when we
  // encounter a page like "02-search" we already know it has children and will
  // be rendered as a SidebarLinkGroup rather than a plain leaf.
  const isFolderParent = new Set<string>();
  for (const entry of entries) {
    const parts = entry.shortId.split("/");
    // Every ancestor segment is a folder parent.
    for (let i = 1; i < parts.length; i++) {
      isFolderParent.add(parts.slice(0, i).join("/"));
    }
  }

  const ensureFolder = (
    segments: string[],
    parentItems: SidebarItem[],
    parentKey: string,
  ): SidebarItem[] => {
    if (segments.length === 0) return parentItems;

    const [head, ...rest] = segments;
    const key = parentKey ? `${parentKey}/${head}` : head;

    if (!folderItems.has(key)) {
      const children: SidebarItem[] = [];

      // If there's a page whose shortId matches this folder key, it becomes a
      // SidebarLinkGroup (clickable heading + nested children).
      const matchingEntry = byStem.get(key);
      if (matchingEntry) {
        const node: SidebarLinkGroup = {
          type: "link-group",
          name: matchingEntry.name,
          url: matchingEntry.url,
          items: children,
        };
        folderNodes.set(key, node);
        parentItems.push(node);
      } else {
        const node: SidebarGroup = {
          type: "group",
          name: humanize(head),
          variant: "nested",
          items: children,
        };
        folderNodes.set(key, node);
        parentItems.push(node);
      }

      folderItems.set(key, children);
    }

    return ensureFolder(rest, folderItems.get(key)!, key);
  };

  for (const entry of entries) {
    const segments = entry.shortId.split("/");
    segments.pop(); // drop filename; only folder segments become groups

    // Skip entries that serve as the "parent page" of a link-group — they are
    // already represented by the link-group node produced by ensureFolder.
    // We use isFolderParent (pre-computed) so this check is order-independent.
    if (isFolderParent.has(entry.shortId)) continue;

    const parentItems = ensureFolder(segments, root, "");
    parentItems.push({ type: "link", name: entry.name, url: entry.url });
  }

  return root;
}

/**
 * Build the pages section as two explicit top-level SidebarGroup sections:
 * "Getting started" and "Reference".
 *
 * Files under 01-getting-started/ -> "Getting started" section (variant "section")
 * Files under 02-reference/       -> "Reference" section (variant "section")
 *
 * Within each section, buildTree() handles nested folders and link-groups.
 */
// `includeHidden` surfaces pages with `hidden: true` frontmatter (and pages
// outside the three known section folders, e.g. the top-level `swagger.mdx`)
// as plain top-level links. The HTML sidebar never passes this — those pages
// stay off the visible nav, e.g. `swagger.mdx` is reachable via the header's
// download icon instead — but llms.txt does, so an LLM crawler relying on it
// as a full site index doesn't miss them.
async function pagesSection(includeHidden = false): Promise<SidebarItem[]> {
  const allEntries = (await getCollection("pages")).sort(byId);

  const result: SidebarItem[] = [];

  // The "index" entry maps to the root "/" path and is handled specially by
  // the catch-all route (slug === undefined -> "/"). Prepend it as a standalone
  // link at the very top of the sidebar so it appears before any section groups.
  const indexEntry = allEntries.find(
    (e) => e.id === "index" && (includeHidden || !e.data.hidden),
  );
  if (indexEntry) {
    result.push({
      type: "link",
      name: indexEntry.data.name ?? indexEntry.data.title,
      url: "/",
    });
  }

  const sectionDefs: Array<{ key: string; label: string }> = [
    { key: "01-getting-started", label: "Getting started" },
    { key: "02-guide", label: "Guide" },
    { key: "03-reference", label: "Reference" },
  ];

  for (const { key, label } of sectionDefs) {
    const sectionEntries: InternalEntry[] = allEntries
      .filter(
        (e) => e.id.startsWith(`${key}/`) && (includeHidden || !e.data.hidden),
      )
      .map((e) => ({
        shortId: e.id.slice(key.length + 1), // strip leading "01-getting-started/"
        name: e.data.name ?? e.data.title,
        url: pagePath(e.id),
      }));

    if (sectionEntries.length === 0) continue;

    result.push({
      type: "group",
      name: label,
      variant: "section",
      items: buildTree(sectionEntries),
    });
  }

  if (includeHidden) {
    const orphanEntries = allEntries.filter(
      (e) =>
        e.id !== "index" &&
        !sectionDefs.some(({ key }) => e.id.startsWith(`${key}/`)),
    );
    for (const e of orphanEntries) {
      result.push({
        type: "link",
        name: e.data.name ?? e.data.title,
        url: pagePath(e.id),
      });
    }
  }

  return result;
}

/** CLI commands — grouped by folder under a "Commands" section heading. */
async function commandsSection(): Promise<SidebarItem[]> {
  const entries = (await getCollection("commands")).sort(byId);
  if (entries.length === 0) return [];

  const internalEntries: InternalEntry[] = entries.map((e) => ({
    shortId: e.id,
    name: e.data.command,
    url: commandPath(e.data.command),
  }));

  const items = buildTree(internalEntries);

  // Ensure all top-level group items use variant "nested"
  // (buildTree already sets variant "nested" for sub-folders, but top-level
  // folder groups inside Commands also need to be nested).
  const nestedItems = items.map((item): SidebarItem =>
    item.type === "group" ? { ...item, variant: "nested" } : item,
  );

  return [
    {
      type: "group",
      name: "Commands",
      variant: "section",
      items: nestedItems,
    },
  ];
}

/** API endpoints — grouped by `group` frontmatter under an "Endpoints" section heading. */
async function apiSection(): Promise<SidebarItem[]> {
  const entries = (await getCollection("endpoints")).sort(byId);

  // Map preserves insertion order, so groups appear in the order their first
  // (lexically-first) endpoint does.
  const groups = new Map<string, SidebarEndpoint[]>();
  for (const e of entries) {
    const item: SidebarEndpoint = {
      type: "endpoint",
      name: e.data.name,
      url: endpointPath(e.data.endpoint),
      method: e.data.method.toLowerCase() as HttpMethod,
      endpointUrl: e.data.endpointUrl,
    };
    if (!groups.has(e.data.group)) groups.set(e.data.group, []);
    groups.get(e.data.group)!.push(item);
  }

  const nestedGroups: SidebarGroup[] = [...groups].map(
    ([group, items]): SidebarGroup => ({
      type: "group",
      name: humanize(group),
      variant: "nested",
      items,
    }),
  );

  if (nestedGroups.length === 0) return [];

  return [
    {
      type: "group",
      name: "Endpoints",
      variant: "section",
      items: nestedGroups,
    },
  ];
}

/** OpenAPI schemas — flat alphabetical list under an "OpenAPI schemas" section heading. */
async function schemaSection(): Promise<SidebarItem[]> {
  const spec = (await SwaggerParser.parse(
    "./src/data/swagger.json",
  )) as OpenAPIObject;
  const schemas = extractSchemas(spec).sort((a, b) =>
    a.name.localeCompare(b.name),
  );

  if (schemas.length === 0) return [];

  const items: SidebarLink[] = schemas.map((s) => ({
    type: "link",
    name: s.name,
    url: schemaPath(s.id),
  }));

  return [
    {
      type: "group",
      name: "OpenAPI schemas",
      variant: "section",
      items,
    },
  ];
}

/**
 * The whole sidebar, top to bottom: content pages, then commands, then API.
 * Reorder the spread to taste.
 *
 * `includeHidden` is forwarded to `pagesSection()` — see its comment. Only
 * llms.txt passes `true`; the HTML sidebar uses the default.
 */
export async function buildSidebar(
  includeHidden = false,
): Promise<SidebarItem[]> {
  const [pages, commands, api, schemas] = await Promise.all([
    pagesSection(includeHidden),
    apiSection(),
    commandsSection(),
    schemaSection(),
  ]);
  return [...pages, ...commands, ...api, ...schemas];
}
