// Central place for the data shape, the type→style registry, and the
// elements → {nodes, edges} transform. Keeping it out of Graph.ts means the
// component file only deals with rendering concerns.

import type { EdgeData, NodeData } from "@antv/g6";

// ── Color scheme ─────────────────────────────────────────────────────────────
//
// Node type colors mirror --c-node-* vars defined in colors.css.
// If you change a color here, update colors.css too (and vice-versa).
// Graph chrome / highlight colors are mirrored in Graph.ts (THEMES / HIGHLIGHTS).

export type Scheme = "light" | "dark";

export interface Theme {
  nodeLabelFill: string;
  edgeStroke: string;
  edgeLabelFill: string;
  edgeLabelBg: string;
  tooltipBg: string;
  tooltipFill: string;
}

// ── Highlight mode ────────────────────────────────────────────────────────────

export interface HlStyle {
  light: string;
  dark: string;
}

// ── Layout and style mode ─────────────────────────────────────────────────────

/** Supported layout variants, selected via the `layout` attribute. */
export type LayoutKind = "antv-dagre-LR" | "antv-dagre-TB" | "grid" | "force";

/** Highlight mode, selected via the `mode` attribute. */
export type StyleMode = "auto" | "manual";

// ── Per-element data fields carried under g6's `data` property ───────────────

export interface NodeDatum {
  type?: string;
  name?: string;
  label?: string;
  highlight?: boolean;
  tooltip?: string;
  hl?: number | string;
}

export interface EdgeDatum {
  name?: string;
  type?: string;
  tooltip?: string;
  hl?: number | string;
}

export interface GraphElement {
  id: string;
  /** semantic type, e.g. "user" / "data" — drives color + icon (NOT the g6 shape type) */
  type?: string;
  /** edge source id — presence of start+end marks this element as a relation */
  start?: string;
  /** edge target id */
  end?: string;
  /** pulsating highlight ring; may also be set inside `data` */
  highlight?: boolean;
  /** hover tooltip text; may also be set inside `data` */
  tooltip?: string;
  /** optional data payload; carried through to nodes and relations alike */
  data?: {
    name?: string;
    label?: string; // legacy fallback
    highlight?: boolean;
    tooltip?: string;
    /**
     * manual-mode highlight key (see HIGHLIGHTS in Graph.ts). Only used when
     * the <g6-graph> is in `mode="manual"`. Simple int|string, e.g. 1, "1",
     * 2, "m". Absent/unknown → muted.
     */
    hl?: number | string;
    [key: string]: unknown;
  };
}

export interface ElementsPayload {
  elements?: GraphElement[];
  // backward-compat: raw g6 data is still accepted as-is
  nodes?: NodeData[];
  edges?: EdgeData[];
}

export interface TypeStyle {
  color: string;
  icon: string; // must match a key in iconUtil.getIcon
}

/**
 * type name -> color + icon.
 * Add new categories here; unknown types fall back to DEFAULT_TYPE.
 */
// Node type color palette — mirrors --c-node-* in colors.css.
export const TYPE_STYLES: Record<string, TypeStyle> = {
  // Identity
  User: { color: "#ef4444", icon: "user" }, // --c-node-user        red-500
  Group: { color: "#f59e0b", icon: "group" }, // --c-node-group       amber-400
  Token: { color: "#d946ef", icon: "token" }, // --c-node-token       fuchsia-500
  // Search
  Search: { color: "#6366f1", icon: "search" }, // --c-node-search      indigo-500
  // Data
  Data: { color: "#2563eb", icon: "data" }, // --c-node-data        blue-600
  Collection: { color: "#16a34a", icon: "collection" }, // --c-node-collection  green-600
  File: { color: "#22d3ee", icon: "file" }, // --c-node-file        cyan-400
  // Taxonomy
  Taxon: { color: "#6b7280", icon: "tag" }, // --c-node-taxon       gray-500
  Plant: { color: "#16a34a", icon: "plant" }, // --c-node-plant       green-600
};

export const DEFAULT_TYPE: TypeStyle = { color: "#6b7280", icon: "data" }; // --c-node-default  gray-500

export function typeStyle(type?: string): TypeStyle {
  return (type && TYPE_STYLES[type]) || DEFAULT_TYPE;
}

const isEdge = (
  el: GraphElement,
): el is GraphElement & { start: string; end: string } =>
  el.start !== undefined && el.end !== undefined;

function firstDefined<T>(...vals: (T | undefined)[]): T | undefined {
  for (const v of vals) if (v !== undefined) return v;
  return undefined;
}

/**
 * Normalises a payload into g6's { nodes, edges } shape. Each produced item
 * carries a normalised `data` block: { ...original, type, name, highlight,
 * tooltip }. `data.hl` (when present) flows through untouched via the spread,
 * for both nodes and relations.
 */
export function elementsToGraphData(payload: ElementsPayload): {
  nodes: NodeData[];
  edges: EdgeData[];
} {
  // If someone still passes raw nodes/edges, pass them straight through.
  if (!payload?.elements && (payload?.nodes || payload?.edges)) {
    return { nodes: payload.nodes ?? [], edges: payload.edges ?? [] };
  }

  const elements = payload?.elements ?? [];
  const nodes: NodeData[] = [];
  const edges: EdgeData[] = [];

  for (const el of elements) {
    const data = {
      ...el.data,
      type: el.type,
      name: firstDefined(el.data?.name, el.data?.label),
      highlight: Boolean(firstDefined(el.highlight, el.data?.highlight)),
      tooltip: firstDefined(el.tooltip, el.data?.tooltip),
    };

    if (isEdge(el)) {
      edges.push({ id: el.id, source: el.start, target: el.end, data });
    } else {
      nodes.push({ id: el.id, data });
    }
  }

  return { nodes, edges };
}
