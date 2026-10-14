// Replaces the four separate DTO files:
//   SidebarItemDto.ts, SidebarLinkItemDto.ts, SidebarEndpointItemDto.ts, SidebarGroupItemDto.ts
//
// A discriminated union (on `type`) lets TypeScript narrow automatically, so the
// rendering code never needs `as SidebarXDto` casts.

export type HttpMethod =
  "get" | "post" | "put" | "patch" | "delete" | "head" | "options";

/** A plain navigation link with no children. */
export interface SidebarLink {
  type: "link";
  name: string;
  url: string;
}

/**
 * A navigation link that also has nested child items.
 * Renders as a clickable link + an indented list of children below it
 * (left border, reduced width).
 */
export interface SidebarLinkGroup {
  type: "link-group";
  name: string;
  url: string;
  items: SidebarItem[];
}

/** An API endpoint link showing the HTTP method badge and endpoint URL. */
export interface SidebarEndpoint {
  type: "endpoint";
  name: string;
  url: string;
  method: HttpMethod;
  endpointUrl: string;
}

/**
 * A non-clickable section heading with nested children.
 * Two visual variants:
 *   - "section": top-level group title (bold/uppercase, larger spacing)
 *   - "nested":  sub-group heading inside a section (smaller, indented with left border)
 */
export interface SidebarGroup {
  type: "group";
  name: string;
  variant: "section" | "nested";
  items: SidebarItem[];
}

export type SidebarItem =
  SidebarLink | SidebarLinkGroup | SidebarEndpoint | SidebarGroup;
