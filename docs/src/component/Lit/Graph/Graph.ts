import { Graph } from "@antv/g6";
import type {
  EdgeData,
  EdgeOptions,
  ElementDatum,
  GraphOptions,
  IElementEvent,
  NodeData,
  NodeOptions,
} from "@antv/g6";
import {
  LitElement,
  type PropertyValues,
  type TemplateResult,
  css,
  html,
} from "lit";

import {
  type EdgeDatum,
  type ElementsPayload,
  type HlStyle,
  type LayoutKind,
  type NodeDatum,
  type Scheme,
  type StyleMode,
  type Theme,
  elementsToGraphData,
  typeStyle,
} from "./graphTypes.ts";
import { iconNodeGeometry } from "./IconNode.ts";
import { escapeHtml } from "../../../util/htmlUtil.ts";

const PAD = 48; // padding around content, both sides
const ZOOM_RANGE: [number, number] = [0.05, 2.0]; // shared by zoomRange + manual pinch clamp

/* ------------------------------------------------------------------ *
 *  Color scheme tokens
 *  Hex values mirror --c-graph-* vars in colors.css. If you change a
 *  color here, update colors.css too (and vice-versa). The G6 API
 *  requires resolved hex/rgba strings, so CSS vars cannot be used here
 *  directly — the CSS file is the single source of truth for the values.
 * ------------------------------------------------------------------ */
const THEMES: Record<Scheme, Theme> = {
  light: {
    nodeLabelFill: "#ffffff",
    edgeStroke: "#8b949e", // --c-graph-edge (light)
    edgeLabelFill: "#3f3f46", // --c-graph-edge-label (light)       zinc-700
    edgeLabelBg: "rgba(255, 255, 255, 0.9)", // --c-graph-edge-label-bg (light)
    tooltipBg: "#1f2430", // --c-graph-tooltip-bg (light)
    tooltipFill: "#ffffff", // --c-graph-tooltip-fg (light)
  },
  dark: {
    nodeLabelFill: "#ffffff",
    edgeStroke: "#6e7681", // --c-graph-edge (dark)
    edgeLabelFill: "#e6edf3", // --c-graph-edge-label (dark)
    edgeLabelBg: "rgba(22, 27, 34, 0.92)", // --c-graph-edge-label-bg (dark)
    tooltipBg: "#0d1117", // --c-graph-tooltip-bg (dark)
    tooltipFill: "#e6edf3", // --c-graph-tooltip-fg (dark)
  },
};

/* ------------------------------------------------------------------ *
 *  Manual ("filter") mode highlight registry
 *
 *  In `mode="manual"` every element is muted by default, and an element
 *  with `data.hl` set adopts the matching color below. Keep these hex
 *  values in sync with:
 *    - highlight.css  (.hl-1 / .hl-2 / .hl-m)
 *    - colors.css     (--c-hl-graph-*-light / --c-hl-graph-*-dark)
 *
 *  hl-1 -> blue-600   (light)  / blue-500   (dark)   --c-hl-graph-1-*
 *  hl-2 -> orange-600 (light)  / orange-500 (dark)   --c-hl-graph-2-*
 *  hl-m -> zinc-700   (light)  / zinc-400   (dark)   --c-hl-graph-m-*  (muted fallback)
 *
 *  `hl` is a simple int|string key (e.g. 1, "1", 2, "m"). Unknown or
 *  absent keys fall back to muted.
 * ------------------------------------------------------------------ */
const HL_MUTED = "m";

const HIGHLIGHTS: Record<string, HlStyle> = {
  m: { light: "#3f3f46", dark: "#a1a1aa" }, // --c-hl-graph-m-*  zinc-700  / zinc-400
  1: { light: "#ff073a", dark: "#ff073a" }, // --c-hl-graph-2-*  orange-600 / orange-500
  2: { light: "#2563eb", dark: "#3b82f6" }, // --c-hl-graph-1-*  blue-600  / blue-500
};

/** Resolve an `hl` key to a fill color for the active scheme. */
function hlColor(hl: unknown, scheme: Scheme): string {
  const key =
    hl === null || hl === undefined || hl === "" ? HL_MUTED : String(hl);
  return (HIGHLIGHTS[key] ?? HIGHLIGHTS[HL_MUTED])[scheme];
}

/** WCAG relative luminance of a #rrggbb color. */
function luminance(hex: string): number {
  const h = hex.replace("#", "");
  const toLin = (c: number): number =>
    c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4;
  const r = toLin(parseInt(h.slice(0, 2), 16) / 255);
  const g = toLin(parseInt(h.slice(2, 4), 16) / 255);
  const b = toLin(parseInt(h.slice(4, 6), 16) / 255);
  return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

/**
 * Pick a label/icon color that stays legible on `bg`. White on the
 * saturated/dark fills, near-black only on genuinely light fills (i.e.
 * the muted gray in dark mode). Threshold sits between orange-500 and
 * zinc-400 so only the light gray flips to dark text.
 *
 * Return values mirror --c-node-label-dark / --c-node-label-light in colors.css.
 */
function readableOn(bg: string): string {
  return luminance(bg) > 0.34 ? "#18181b" : "#ffffff"; // zinc-900 vs white
}

const nodeDatum = (d: NodeData): NodeDatum => (d.data ?? {}) as NodeDatum;
const edgeDatum = (d: EdgeData): EdgeDatum => (d.data ?? {}) as EdgeDatum;
const nodeLabel = (d: NodeData): string => {
  const x = nodeDatum(d);
  return String(x.name ?? x.label ?? d.id);
};

export class G6Graph extends LitElement {
  static styles = css`
    :host {
      display: block;
      width: 100%;
      min-height: 80px;
    }

    .wrapper {
      position: relative;
      width: 100%;
      height: 100%;
      overflow: hidden;
    }

    .canvas {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      touch-action: none;
    }

    :host([variant="good"]) .wrapper {
      background-color: color-mix(in oklab, var(--color-green-50) 50%, white);
    }
    :host([variant="bad"]) .wrapper {
      background-color: color-mix(in oklab, var(--color-red-50) 50%, white);
    }
    @media (prefers-color-scheme: dark) {
      :host([variant="good"]) .wrapper {
        background-color: color-mix(
          in oklab,
          var(--color-green-950) 62.5%,
          black
        );
      }
      :host([variant="bad"]) .wrapper {
        background-color: color-mix(
          in oklab,
          var(--color-red-950) 62.5%,
          black
        );
      }
    }
  `;

  static properties = {
    layout: { type: String, reflect: true },
    scheme: { type: String, reflect: true },
    variant: { type: String, reflect: true },
    mode: { type: String, reflect: true },
  };

  /** Layout variant. */
  declare layout: LayoutKind;
  /** 'light' (default) | 'dark' | 'auto' (follow prefers-color-scheme). */
  declare scheme: "light" | "dark" | "auto";
  declare variant?: "good" | "bad";
  /**
   * 'auto' (default): type-driven colors + icons (original behavior).
   * 'manual': everything muted; `data.hl` drives the highlight color.
   */
  declare mode: StyleMode;

  constructor() {
    super();
    this.layout = "antv-dagre-LR";
    this.scheme = "auto";
    this.mode = "auto";
  }

  private _graph: Graph;
  private _tips = new Map<string, string>();
  private _ro: ResizeObserver | null = null;
  private _lastWidth = 0;
  private _mql: MediaQueryList | null = null;
  private _firstPaintDone = false;

  // --- Cross-instance height sync (for side-by-side comparison cards) ---
  // Each instance computes its own "ideal" height from its own content at
  // zoom 1. When sitting inside a `.two-column` group on a wide viewport,
  // instances agree on the tallest ideal height among the group and all
  // resize/refit to that shared height, so a smaller graph grows to match
  // its sibling instead of leaving (or causing) mismatched card heights.
  private _idealH = 0;
  private _appliedH = 0;

  // --- Manual pinch-zoom state (scoped to THIS component's own canvas) ---
  // We deliberately do NOT use G6's `trigger: ['pinch']` behavior: its touch
  // gesture isn't cleanly scoped per-canvas, so with several graphs mounted a
  // single pinch drives only the first graph and they appear linked. Handling
  // pointer events on our own shadow-root canvas keeps each instance isolated.
  private _canvasEl: HTMLElement | null = null;
  private _pointers = new Map<number, { x: number; y: number }>();
  private _pinchStartDist = 0;
  private _pinchStartZoom = 1;
  private _dragSuspended = false;

  // Stable reference so we can add/remove the same listener.
  private _onSchemeChange = (): void => {
    if (this.scheme === "auto") this._applyTheme();
  };

  /** Resolve the effective scheme, honoring OS preference when 'auto'. */
  private get _scheme(): Scheme {
    const prefersDark = this._mql
      ? this._mql.matches
      : typeof window !== "undefined" &&
        Boolean(window.matchMedia?.("(prefers-color-scheme: dark)").matches);
    return this.scheme === "auto"
      ? prefersDark
        ? "dark"
        : "light"
      : this.scheme;
  }

  private get _theme(): Theme {
    return THEMES[this._scheme];
  }

  render(): TemplateResult {
    return html` <div class="wrapper">
      <div class="canvas"></div>
    </div>`;
  }

  connectedCallback(): void {
    super.connectedCallback();
    if (typeof window !== "undefined" && window.matchMedia) {
      this._mql = window.matchMedia("(prefers-color-scheme: dark)");
      this._mql.addEventListener("change", this._onSchemeChange);
    }
  }

  /** Build the layout spec for the current `layout` variant. */
  private _layoutOptions(): GraphOptions["layout"] {
    switch (this.layout) {
      case "antv-dagre-LR":
        return {
          type: "antv-dagre",
          rankdir: "LR",
          nodesep: 30,
          ranksep: 80,
          controlPoints: true,
          nodeSize: 120,
        };
      case "antv-dagre-TB":
        return {
          type: "antv-dagre",
          rankdir: "TB",
          nodesep: 30,
          ranksep: 80,
          controlPoints: true,
          nodeSize: 120,
        };
      case "force":
        return {
          type: "force",
          preventOverlap: true,
          nodeSize: 100,
        };
      case "grid":
      default:
        return {
          type: "grid",
          preventOverlap: true,
          nodeSize: 100,
          condense: false,
        };
    }
  }

  /** Behaviors. Wheel/trackpad zoom stays native; pinch is handled manually. */
  private _behaviorOptions(): GraphOptions["behaviors"] {
    return [
      // Default trigger is the wheel; trackpad pinch arrives as wheel+ctrlKey,
      // so desktop (mouse + laptop trackpad) is fully covered here.
      { type: "zoom-canvas", key: "zoom-wheel" },
      { type: "drag-canvas", key: "drag-canvas" },
    ];
  }

  /**
   * Per-node fill. In manual mode this is the `hl` color (muted fallback);
   * in auto mode it's the type color. Shared by `fill` and `haloStroke`.
   */
  private _nodeFill(d: NodeData, scheme: Scheme): string {
    return this.mode === "manual"
      ? hlColor(nodeDatum(d).hl, scheme)
      : typeStyle(nodeDatum(d).type).color;
  }

  /** Build the node spec. Reused for initial render and theme swaps. */
  private _nodeOptions(): NodeOptions {
    const t = this._theme;
    const scheme = this._scheme;
    const manual = this.mode === "manual";

    return {
      type: "icon-node",
      animation: false,
      style: {
        fill: (d: NodeData) => this._nodeFill(d, scheme),
        // Icon shape always reflects the type; only its color is themed below.
        iconType: (d: NodeData) => typeStyle(nodeDatum(d).type).icon,
        iconSize: 24,
        labelText: (d: NodeData) => nodeLabel(d),
        // Auto mode keeps the original white label; manual mode auto-contrasts
        // against the resolved fill (so the light muted gray gets dark text).
        labelFill: (d: NodeData) =>
          manual ? readableOn(this._nodeFill(d, scheme)) : t.nodeLabelFill,
        labelFontSize: 14,
        labelFontWeight: 600,
        labelFontFamily: "Fira Code",
        highlight: (d: NodeData) => Boolean(nodeDatum(d).highlight),
        halo: (d: NodeData) => Boolean(nodeDatum(d).highlight),
        haloStroke: (d: NodeData) => this._nodeFill(d, scheme),
        haloOpacity: 0.35,
        size: (d: NodeData) =>
          2 *
          iconNodeGeometry({
            labelText: nodeLabel(d),
            fontSize: 14,
            fontFamily: "Fira Code",
            iconSize: 24,
          }).outerCircleRadius,
      },
    };
  }

  /** Build the edge spec. Reused for initial render and theme swaps. */
  private _edgeOptions(): EdgeOptions {
    const t = this._theme;
    const scheme = this._scheme;
    const manual = this.mode === "manual";

    // In manual mode the stroke (and the arrow, which inherits it) takes the
    // edge's `hl` color; the label text is tinted to match for prose unity.
    const strokeOf = (d: EdgeData): string =>
      manual ? hlColor(edgeDatum(d).hl, scheme) : t.edgeStroke;

    return {
      type: "polyline",
      animation: false,
      style: {
        endArrow: true,
        endArrowType: "triangle",
        stroke: strokeOf,
        lineWidth: 2,
        labelText: (d: EdgeData) =>
          edgeDatum(d).name ?? edgeDatum(d).type ?? "",
        labelFill: manual ? strokeOf : t.edgeLabelFill,
        labelFontSize: 14,
        labelFontWeight: 600,
        labelFontFamily: "Fira Code",
        radius: 20,
        labelBackground: true,
        labelPadding: 5,
        labelBackgroundFill: t.edgeLabelBg,
      },
    };
  }

  firstUpdated(): void {
    const payload = this._readData();
    if (!payload) return;

    const data = elementsToGraphData(payload);

    this._tips.clear();
    for (const item of [...data.nodes, ...data.edges]) {
      const tip = (item?.data as { tooltip?: unknown } | undefined)?.tooltip;
      if (tip) this._tips.set(item.id as string, String(tip));
    }

    const options = {
      container: this.renderRoot.querySelector(".canvas") as HTMLElement,
      data,
      animation: false,
      autoResize: false,
      zoomRange: ZOOM_RANGE,
      padding: PAD,
      layout: this._layoutOptions(),
      node: this._nodeOptions(),
      edge: this._edgeOptions(),
      behaviors: this._behaviorOptions(),
      plugins: [
        {
          type: "tooltip",
          enable: (e: IElementEvent, items?: ElementDatum[]): boolean =>
            this._tips.has(this._eid(e, items) ?? ""),
          getContent: (e: IElementEvent, items?: ElementDatum[]): string => {
            const tip = this._tips.get(this._eid(e, items) ?? "");
            if (!tip) return "";
            const t = this._theme;
            return (
              `<div style="padding:6px 9px;border-radius:6px;` +
              `font:12px/1.4 'Fira Code',monospace;color:${t.tooltipFill};` +
              `background:${t.tooltipBg};box-shadow:0 1px 6px rgba(0,0,0,.35);` +
              `max-width:240px;white-space:normal;">` +
              escapeHtml(tip) +
              `</div>`
            );
          },
        },
      ],
    } satisfies GraphOptions;

    this._graph = new Graph(options);

    this._graph
      .render()
      .then(async () => {
        await this._fitAndSize();
        this._firstPaintDone = true;
        return;
      })
      .catch((err: unknown) => {
        // Intentional diagnostic — surfaces render failures in the
        // reader's browser console.
        // eslint-disable-next-line no-console
        console.error("[g6-graph] Failed to render graph:", err);
      });

    // Manual pinch-zoom: bind pointer events to our own canvas element so the
    // gesture stays isolated to this component instance.
    this._canvasEl = this.renderRoot.querySelector(".canvas") as HTMLElement;
    this._canvasEl.addEventListener("pointerdown", this._onPointerDown);
    this._canvasEl.addEventListener("pointermove", this._onPointerMove, {
      passive: false,
    });
    this._canvasEl.addEventListener("pointerup", this._onPointerUp);
    this._canvasEl.addEventListener("pointercancel", this._onPointerUp);

    // Only react to WIDTH changes to avoid feedback loop from our own height writes
    this._ro = new ResizeObserver((entries) => {
      const w =
        entries[0]?.contentBoxSize?.[0]?.inlineSize ?? this.clientWidth ?? 0;
      if (Math.abs(w - this._lastWidth) < 1) return; // height-only change → ignore
      this._lastWidth = w;
      this._fitAndSize();
    });
    this._ro.observe(this);
  }

  updated(changed: PropertyValues): void {
    // React to runtime scheme changes (the initial scheme is already baked
    // into the first render via the option builders, so skip until painted).
    if (this._firstPaintDone && changed.has("scheme")) this._applyTheme();
    // React to runtime mode changes the same way: rebuild node/edge specs.
    if (this._firstPaintDone && changed.has("mode")) this._applyTheme();
    // React to runtime layout changes: re-run layout, then refit/resize.
    if (this._firstPaintDone && changed.has("layout")) this._applyLayout();
  }

  /* -------------------------------------------------------------- *
   *  Manual pinch-zoom handlers
   * -------------------------------------------------------------- */

  private _onPointerDown = (e: PointerEvent): void => {
    if (e.pointerType !== "touch") return;
    this._canvasEl?.setPointerCapture?.(e.pointerId);
    this._pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
    if (this._pointers.size === 2) {
      const [a, b] = [...this._pointers.values()];
      this._pinchStartDist = Math.hypot(a.x - b.x, a.y - b.y);
      this._pinchStartZoom = this._graph?.getZoom() ?? 1;
      // Stop drag-canvas from panning off one finger mid-pinch.
      if (this._graph && !this._dragSuspended) {
        this._graph.updateBehavior({ key: "drag-canvas", enable: false });
        this._dragSuspended = true;
      }
    }
  };

  private _onPointerMove = (e: PointerEvent): void => {
    if (!this._pointers.has(e.pointerId) || !this._graph) return;
    this._pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
    if (this._pointers.size !== 2 || this._pinchStartDist <= 0) return;

    const [a, b] = [...this._pointers.values()];
    const dist = Math.hypot(a.x - b.x, a.y - b.y);
    const ratio = dist / this._pinchStartDist;

    // Clamp to the graph's zoomRange.
    const target = Math.min(
      ZOOM_RANGE[1],
      Math.max(ZOOM_RANGE[0], this._pinchStartZoom * ratio),
    );

    // Midpoint of the two fingers, in canvas-local (viewport) coordinates.
    const rect = this._canvasEl!.getBoundingClientRect();
    const ox = (a.x + b.x) / 2 - rect.left;
    const oy = (a.y + b.y) / 2 - rect.top;

    e.preventDefault();
    // (ratio, animation, origin) — same arg order used elsewhere in this file.
    this._graph.zoomTo(target, false, [ox, oy]);
  };

  private _onPointerUp = (e: PointerEvent): void => {
    this._pointers.delete(e.pointerId);
    if (this._pointers.size < 2) {
      this._pinchStartDist = 0;
      if (this._graph && this._dragSuspended) {
        this._graph.updateBehavior({ key: "drag-canvas", enable: true });
        this._dragSuspended = false;
      }
    }
  };

  /** Re-skin the graph in place for the current scheme/mode — no relayout. */
  private async _applyTheme(): Promise<void> {
    if (!this._graph) return;
    this._graph.setNode(this._nodeOptions());
    this._graph.setEdge(this._edgeOptions());
    await this._graph.draw(); // re-derives styles; camera/zoom preserved
  }

  /** Swap to the current `layout` variant and re-run layout in place. */
  private async _applyLayout(): Promise<void> {
    if (!this._graph) return;
    this._graph.setLayout(this._layoutOptions());
    await this._graph.layout();
    await this._fitAndSize();
  }

  /* -------------------------------------------------------------- *
   *  Cross-instance height sync
   * -------------------------------------------------------------- */

  /** True when sibling comparison columns are laid out side by side. */
  private _isSideBySide(): boolean {
    // Matches the `md:` breakpoint used by TwoColumn.astro's `flex-col md:flex-row`.
    return (
      typeof window !== "undefined" &&
      window.matchMedia("(min-width: 768px)").matches
    );
  }

  /** Other <g6-graph> instances sharing this element's `.two-column` group. */
  private _groupMembers(): G6Graph[] {
    const group = this.closest(".two-column");
    return group
      ? (Array.from(group.querySelectorAll("g6-graph")) as G6Graph[])
      : [this];
  }

  /**
   * Agree with sibling graphs in the same `.two-column` group on a shared
   * height (the tallest "ideal" height among the group), then have every
   * member refit its content into that height. On narrow viewports (cards
   * stacked, not side by side) each instance just uses its own ideal height.
   *
   * Safe to call repeatedly/concurrently: whichever instance currently has
   * the tallest ideal height wins and re-applies to the whole group, so a
   * late-finishing taller sibling still corrects an already-sized shorter one.
   */
  private async _syncGroupHeight(localIdeal: number): Promise<void> {
    this._idealH = localIdeal;

    if (!this._isSideBySide()) {
      // On mobile the cards stack vertically, each filling its own height.
      // Apply this instance's ideal height, then nudge every sibling in the
      // group to (re-)apply its own ideal height so stale desktop-synced
      // heights don't leave a graph smaller than its card.
      const members = this._groupMembers();
      await Promise.all(
        members.map((m) =>
          m._idealH > 0 ? m._applyHeight(m._idealH) : Promise.resolve(),
        ),
      );
      return;
    }

    const members = this._groupMembers();
    const target = Math.max(...members.map((m) => m._idealH || 0));

    await Promise.all(
      members
        .filter((m) => m._appliedH !== target)
        .map((m) => m._applyHeight(target)),
    );
  }

  /** Apply a (possibly externally-decided) height and refit content into it. */
  private async _applyHeight(h: number): Promise<void> {
    this._appliedH = h;
    this.style.height = `${h}px`;

    if (!this._graph) return; // not initialized yet — it will resync once it is

    const wrapper = this.renderRoot.querySelector(".wrapper") as HTMLElement;
    const containerW = wrapper?.clientWidth ?? this.clientWidth;
    if (!containerW) return;

    this._graph.setSize(containerW, h);

    await new Promise<void>((resolve) =>
      requestAnimationFrame(() => requestAnimationFrame(() => resolve())),
    );

    await this._graph.fitView({ when: "always", direction: "both" });
  }

  private async _fitAndSize(): Promise<void> {
    if (!this._graph) return;

    const nodes = this._graph.getNodeData();
    if (!nodes?.length) return;

    const wrapper = this.renderRoot.querySelector(".wrapper") as HTMLElement;
    const containerW = wrapper?.clientWidth ?? this.clientWidth;
    if (!containerW) return;

    await this._graph.zoomTo(1, false);
    await this._graph.translateTo([0, 0], false);

    await new Promise<void>((resolve) =>
      requestAnimationFrame(() => resolve()),
    );

    let minX = Infinity,
      minY = Infinity,
      maxX = -Infinity,
      maxY = -Infinity;
    for (const node of nodes) {
      try {
        const b = this._graph.getElementRenderBounds(node.id);
        if (!b) continue;
        if (b.min[0] < minX) minX = b.min[0];
        if (b.min[1] < minY) minY = b.min[1];
        if (b.max[0] > maxX) maxX = b.max[0];
        if (b.max[1] > maxY) maxY = b.max[1];
      } catch {
        /* not yet rendered */
      }
    }
    if (!isFinite(minY) || !isFinite(maxY)) return;

    const contentW = maxX - minX;
    const contentH = maxY - minY;

    const availW = containerW - PAD * 2;
    const zoomFromWidth = contentW > 0 ? Math.min(availW / contentW, 1) : 1;
    const neededH = Math.ceil(contentH * zoomFromWidth) + PAD * 2;

    // Instead of applying neededH directly, reconcile with sibling graphs in
    // the same .two-column group so both sides end up the same height.
    await this._syncGroupHeight(neededH);
  }

  private _eid(e: IElementEvent, items?: ElementDatum[]): string | undefined {
    return (
      (items?.[0]?.id as string | undefined) ??
      (e.target as { id?: string } | undefined)?.id
    );
  }

  _readData(): ElementsPayload | null {
    const script = this.querySelector('script[type="application/json"]');
    let raw = (script ? script.textContent : this.textContent) || "";
    raw = raw.trim();
    raw = raw.replace(/^\{`/, "").replace(/`\}$/, "").trim();
    if (!raw) {
      // Intentional diagnostic — malformed content in the page's own
      // embedded JSON, worth surfacing in the reader's browser console.
      // eslint-disable-next-line no-console
      console.warn("[g6-graph] No JSON data found in element.");
      return null;
    }
    try {
      return JSON.parse(raw) as ElementsPayload;
    } catch (err) {
      // eslint-disable-next-line no-console -- see rationale above
      console.error("[g6-graph] Failed to parse graph JSON data:", err);
      return null;
    }
  }

  disconnectedCallback(): void {
    super.disconnectedCallback();
    this._mql?.removeEventListener("change", this._onSchemeChange);
    this._ro?.disconnect();

    // Tear down manual pinch listeners.
    this._canvasEl?.removeEventListener("pointerdown", this._onPointerDown);
    this._canvasEl?.removeEventListener("pointermove", this._onPointerMove);
    this._canvasEl?.removeEventListener("pointerup", this._onPointerUp);
    this._canvasEl?.removeEventListener("pointercancel", this._onPointerUp);
    this._pointers.clear();
    this._canvasEl = null;

    this._graph?.destroy();
  }
}

customElements.define("g6-graph", G6Graph);
