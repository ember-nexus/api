// Shared prev/next computation for both render targets: `PageNav.astro` (HTML,
// bottom-of-page cards) and the markdown footer nav (`mdmx/footerNav.ts`).
// Both flatten the sidebar into a single ordered list per top-level category
// and walk outward from the current page, so the two render targets can't
// silently compute different "next" pages for the same content.
import { buildSidebar } from "./sidebar.ts";
import type { HttpMethod, SidebarItem } from "../type";

export type NavCategory = "page" | "command" | "endpoint" | "schema";

export interface NavItem {
  name: string;
  url: string;
  category: NavCategory;
  method?: HttpMethod;
  endpointUrl?: string;
}

const sectionCategory = (name: string): NavCategory | null => {
  const lower = name.toLowerCase();
  if (lower === "getting started" || lower === "guide" || lower === "reference")
    return "page";
  if (lower === "commands") return "command";
  if (lower === "endpoints") return "endpoint";
  if (lower === "schemas") return "schema";
  return null;
};

const flatten = (
  items: SidebarItem[],
  category: NavCategory | null,
): NavItem[] => {
  const result: NavItem[] = [];

  for (const item of items) {
    if (item.type === "group") {
      if (item.variant === "section") {
        const cat = sectionCategory(item.name);
        if (cat) {
          result.push(...flatten(item.items, cat));
        }
      } else {
        result.push(...flatten(item.items, category));
      }
      continue;
    }

    const cat = category ?? "page";

    if (item.type === "link-group") {
      result.push({ name: item.name, url: item.url, category: cat });
      result.push(...flatten(item.items, cat));
    } else if (item.type === "link") {
      result.push({ name: item.name, url: item.url, category: cat });
    } else if (item.type === "endpoint") {
      result.push({
        name: item.name,
        url: item.url,
        category: cat,
        method: item.method,
        endpointUrl: item.endpointUrl,
      });
    }
  }

  return result;
};

const normalizePath = (path: string): string =>
  path === "/" ? path : path.replace(/\/$/, "");

/** The whole site flattened into one ordered list, sidebar order preserved, tagged by category. */
export async function buildNavItems(): Promise<NavItem[]> {
  return flatten(await buildSidebar(), null);
}

/**
 * The previous/next page *within the same category* as `currentPath` (pages
 * don't page through commands, endpoints don't page through schemas, ...).
 * Returns `{}` if `currentPath` isn't found in `items` at all.
 */
export function findPrevNext(
  items: NavItem[],
  currentPath: string,
): { prev?: NavItem; next?: NavItem } {
  const currentIndex = items.findIndex(
    (item) => normalizePath(item.url) === normalizePath(currentPath),
  );
  if (currentIndex === -1) return {};

  const category = items[currentIndex].category;

  const prev = items
    .slice(0, currentIndex)
    .reverse()
    .find((item) => item.category === category);

  const next = items
    .slice(currentIndex + 1)
    .find((item) => item.category === category);

  return { prev, next };
}
