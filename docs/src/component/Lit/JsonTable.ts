import "@vaadin/grid";
import type { GridActiveItemChangedEvent } from "@vaadin/grid";
import { gridRowDetailsRenderer } from "@vaadin/grid/lit.js";
import "@vaadin/grid/vaadin-grid-column.js";
import { LitElement, type TemplateResult, css, html } from "lit";

import { escapeHtml } from "../../util/htmlUtil.ts";

// ── Types ────────────────────────────────────────────────────────────────────

/** A loosely typed record from the JSON payload */
type JsonRecord = Record<string, unknown>;

/** Detected "element" shape: has id + type */
interface Element extends JsonRecord {
  id: string;
  type: string;
  start?: string;
  end?: string;
  data?: JsonRecord;
}

const isElement = (r: JsonRecord): r is Element =>
  typeof r.id === "string" && typeof r.type === "string";

const CLIP_LENGTH = 60;
const UUID_PREFIX_LENGTH = 8;

const clip = (s: string): string =>
  s.length > CLIP_LENGTH ? s.slice(0, CLIP_LENGTH) + "…" : s;

const stringify = (v: unknown): string => {
  if (v === null || v === undefined) return "";
  if (typeof v === "object") return JSON.stringify(v);
  return String(v);
};

// ── Column definition ────────────────────────────────────────────────────────

interface ColDef {
  key: string; // dot-path or flat key used for display
  header: string;
  priority: number; // lower = further left
}

const UUID_KEYS = new Set(["id", "start", "end"]);

/**
 * Derive column definitions from the records.
 *
 * Priority rules:
 *   0  type
 *   1  id
 *   2  start
 *   3  end
 *   4  data.name / name
 *   5  everything else (alphabetical)
 *
 * Nested objects (other than data.name) are rendered as clipped JSON.
 * The `data` bag is flattened one level: data.name → shown as "name", etc.
 *
 * The `start` / `end` columns are only emitted when at least one record
 * actually carries those properties (i.e. there are relationships present).
 * If every element is a plain node, those columns are omitted entirely.
 */
function deriveColumns(records: JsonRecord[], hasElements: boolean): ColDef[] {
  const seen = new Map<string, ColDef>();

  const add = (key: string, header: string, priority: number): void => {
    if (!seen.has(key)) seen.set(key, { key, header, priority });
  };

  if (hasElements) {
    add("type", "Type", 0);
    add("id", "ID", 1);

    // Only show start/end if some record is actually a relationship.
    const hasStart = records.some(
      (r) => r.start !== undefined && r.start !== null,
    );
    const hasEnd = records.some((r) => r.end !== undefined && r.end !== null);
    if (hasStart) add("start", "Start", 2);
    if (hasEnd) add("end", "End", 3);
  }

  for (const rec of records) {
    for (const [k] of Object.entries(rec)) {
      if (["id", "type", "start", "end", "data"].includes(k)) continue;
      const p = k === "name" ? 4 : 10;
      add(k, k, p);
    }

    // flatten data one level
    if (rec.data && typeof rec.data === "object") {
      for (const [k] of Object.entries(rec.data as JsonRecord)) {
        const key = `data.${k}`;
        const p = k === "name" ? 4 : 10;
        add(key, k, p);
      }
    }
  }

  // remove data.* columns where the value is always an object/array
  // (those are too noisy for a column — they live in row details only)
  const bulky = new Set<string>();
  for (const col of seen.values()) {
    if (!col.key.startsWith("data.")) continue;
    const subKey = col.key.slice("data.".length);
    const allBulky = records.every((r) => {
      const d = (r.data ?? {}) as JsonRecord;
      const v = d[subKey];
      return v !== undefined && typeof v === "object";
    });
    if (allBulky) bulky.add(col.key);
  }
  bulky.forEach((k) => seen.delete(k));

  return [...seen.values()].sort(
    (a, b) => a.priority - b.priority || a.key.localeCompare(b.key),
  );
}

/** Read a (possibly dotted) key from a record */
function readKey(rec: JsonRecord, key: string): unknown {
  if (key.includes(".")) {
    const [head, ...rest] = key.split(".");
    const child = rec[head];
    if (child && typeof child === "object")
      return readKey(child as JsonRecord, rest.join("."));
    return undefined;
  }
  return rec[key];
}

// ── LitElement ───────────────────────────────────────────────────────────────

export class JsonTable extends LitElement {
  static styles = css`
    :host {
      display: block;
      font-family: "Fira Code", monospace;
    }

    vaadin-grid {
      height: auto;
      max-height: 600px;
    }

    /* Vertically centre cell content. Custom renderers otherwise leave the
           slotted content top-aligned, which is why the rows looked off. */
    vaadin-grid::part(cell) {
      align-items: center;
    }

    /* The detail row is just the code block. Strip Vaadin's own padding and
           match its background to the code block so the pre's rounded corners
           don't reveal white pixels of the cell behind them. */
    vaadin-grid::part(details-cell) {
      padding: 0;
      background: var(--json-bg, var(--c-json-bg, #1e293b));
    }

    .cell {
      font-size: 0.8rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      /* Clip at the actual column edge rather than a fixed pixel width,
               so wider columns reveal more text. */
      max-width: 100%;
      display: block;
    }

    /* Wrapper exists purely to give auto-width a few px of slack so the
           badge's horizontal padding / rounded corners are never clipped. */
    .type-cell {
      display: inline-flex;
      padding-right: 6px;
    }

    /* The type badge must always be shown in full — no clipping. */
    .type-badge {
      display: inline-block;
      padding: 2px 9px;
      border-radius: 9999px;
      font-size: 0.72rem;
      font-weight: 600;
      background: var(--type-badge-bg, var(--c-json-type-badge-bg, #334155));
      color: var(--type-badge-fg, var(--c-json-type-badge-fg, #e2e8f0));
      letter-spacing: 0.03em;
      white-space: nowrap;
    }

    /* Truncated uuids: exactly 8 chars, never clipped (the column has a
           fixed width wide enough to hold them). */
    .mono-fixed {
      font-family: "Fira Code", monospace;
      font-size: 0.72rem;
      color: var(--id-color, var(--c-json-id, #94a3b8));
      white-space: nowrap;
    }

    /* ── row details ── */
    pre.json-detail {
      margin: 0;
      padding: 0.75rem 1rem;
      width: 100%;
      box-sizing: border-box;
      border-radius: 0.5rem; /* enforced radius — tweak later if needed */
      background: var(--json-bg, var(--c-json-bg, #1e293b));
      color: var(--json-fg, var(--c-json-fg, #e2e8f0));
      font-size: 0.8rem;
      line-height: 1.55;
      overflow-x: auto;
      white-space: pre;
      tab-size: 2;
    }

    /* syntax highlight tokens — values mirror --c-json-* in colors.css */
    .json-key {
      color: var(--c-json-key, #7dd3fc);
    } /* sky-300   */
    .json-str {
      color: var(--c-json-str, #86efac);
    } /* green-300 */
    .json-num {
      color: var(--c-json-num, #fbbf24);
    } /* amber-400 */
    .json-bool {
      color: var(--c-json-bool, #f472b6);
    } /* pink-400  */
    .json-null {
      color: var(--c-json-null, #94a3b8);
    } /* slate-400 */
  `;

  static properties = {
    src: { type: String, reflect: true },
  };

  /** Optional: pass JSON as attribute string. Otherwise reads inner <script type="application/json"> */
  declare src: string | undefined;

  private _records: JsonRecord[] = [];
  private _columns: ColDef[] = [];
  private _hasElements = false;
  private _openItems: JsonRecord[] = [];

  connectedCallback(): void {
    super.connectedCallback();
    this._parseData();
  }

  firstUpdated(): void {
    // The (auto-width) type column gets measured immediately, possibly
    // before the 'Fira Code' web font has loaded. Re-measuring once fonts
    // are ready prevents the type badge from being clipped.
    const grid = this.renderRoot.querySelector("vaadin-grid") as
      (HTMLElement & { recalculateColumnWidths?: () => void }) | null;
    const recalc = (): void => grid?.recalculateColumnWidths?.();

    const fonts = (
      document as unknown as { fonts?: { ready?: Promise<unknown> } }
    ).fonts;
    if (fonts?.ready?.then) {
      fonts.ready.then(recalc).catch(() => {
        // Font loading failed — fall back to the already-measured widths.
      });
    } else {
      requestAnimationFrame(recalc);
    }
  }

  private _parseData(): void {
    let raw: string;
    if (this.src) {
      raw = this.src;
    } else {
      const script = this.querySelector('script[type="application/json"]');
      raw = (script ? script.textContent : this.textContent) ?? "";
    }
    raw = raw.trim();
    try {
      const parsed = JSON.parse(raw);
      if (!Array.isArray(parsed)) {
        // Intentional diagnostic — malformed content in the page's own
        // embedded JSON, worth surfacing in the reader's browser console.
        // eslint-disable-next-line no-console
        console.warn("JsonTable: expected JSON array");
        return;
      }
      this._records = parsed as JsonRecord[];
      this._hasElements = this._records.some(isElement);
      this._columns = deriveColumns(this._records, this._hasElements);
    } catch (e) {
      // eslint-disable-next-line no-console -- see rationale above
      console.error("JsonTable: JSON parse error", e);
    }
  }

  // ── rendering ────────────────────────────────────────────────────────────

  render(): TemplateResult {
    if (!this._records.length) {
      return html`<p
        style="color:var(--c-json-null,#94a3b8);font-size:0.85rem;"
      >
        No data.
      </p>`;
    }

    return html`
      <vaadin-grid
        .items="${this._records}"
        .detailsOpenedItems="${this._openItems}"
        @active-item-changed="${this._onActiveItemChanged}"
        theme="row-stripes compact"
        ${gridRowDetailsRenderer<JsonRecord>(
          (rec) => this._renderDetail(rec),
          [],
        )}
      >
        ${this._columns.map((col) => this._renderColumn(col))}
      </vaadin-grid>
    `;
  }

  private _renderColumn(col: ColDef): TemplateResult {
    const isType = col.key === "type";
    const isUuid = UUID_KEYS.has(col.key);

    // type  → auto-width, sized to the badge
    // uuid  → fixed width, always shows the 8-char prefix
    // other → 170px base, grows to fill
    const width = isType ? undefined : isUuid ? "100px" : "170px";
    const flexGrow = isType || isUuid ? 0 : 1;

    return html`
      <vaadin-grid-column
        .header="${col.header}"
        .autoWidth="${isType}"
        .flexGrow="${flexGrow}"
        .width="${width}"
        .renderer="${(
          root: HTMLElement,
          _col: unknown,
          model: { item: JsonRecord },
        ): void => {
          const val = readKey(model.item, col.key);
          const full = stringify(val);

          if (isType) {
            // Full value, never clipped, with slack for the rounded badge.
            root.innerHTML = `<span class="type-cell"><span class="type-badge">${escapeHtml(full)}</span></span>`;
          } else if (isUuid) {
            // Only the first 8 chars of the uuid; full value in the tooltip.
            const short = full.slice(0, UUID_PREFIX_LENGTH);
            root.innerHTML = `<span class="mono-fixed" title="${escapeHtml(full)}">${escapeHtml(short)}</span>`;
          } else {
            root.innerHTML = `<span class="cell" title="${escapeHtml(full)}">${escapeHtml(clip(full))}</span>`;
          }
        }}"
      ></vaadin-grid-column>
    `;
  }

  private _renderDetail(rec: JsonRecord): TemplateResult {
    return html`
      <pre class="json-detail">
${this._highlightJson(JSON.stringify(rec, null, 2))}</pre>
    `;
  }

  /**
   * Lightweight, dependency-free JSON syntax highlighting.
   *
   * Implemented as a single-pass tokenizer rather than a series of
   * replace() calls. The previous approach applied a `:\s*(\d+)` → `': $1'`
   * rule across the whole blob, which rewrote colons *inside* string values
   * (e.g. ISO timestamps like 2025-09-25T18:35:26+00:00 became
   * "18: 35: 26+00: 00"). Matching whole string tokens first means the
   * contents of a string are never re-processed.
   */
  private _highlightJson(json: string): Node {
    // Order of alternatives matters: a complete string is matched as one
    // token (so its inner colons/digits are left alone). A string token
    // immediately followed by `:` is a key.
    const tokenRe =
      /("(?:\\.|[^"\\])*")(\s*:)?|(-?\d+(?:\.\d+)?(?:[eE][+-]?\d+)?)|(true|false)|(null)/g;

    let out = "";
    let lastIndex = 0;
    let m: RegExpExecArray | null;

    while ((m = tokenRe.exec(json)) !== null) {
      // Structural characters / whitespace between tokens — escape verbatim.
      out += escapeHtml(json.slice(lastIndex, m.index));
      lastIndex = tokenRe.lastIndex;

      if (m[1] !== undefined) {
        // string token (optionally a key if followed by a colon)
        const str = escapeHtml(m[1]);
        if (m[2] !== undefined) {
          out += `<span class="json-key">${str}</span>${escapeHtml(m[2])}`;
        } else {
          out += `<span class="json-str">${str}</span>`;
        }
      } else if (m[3] !== undefined) {
        out += `<span class="json-num">${m[3]}</span>`;
      } else if (m[4] !== undefined) {
        out += `<span class="json-bool">${m[4]}</span>`;
      } else if (m[5] !== undefined) {
        out += `<span class="json-null">${m[5]}</span>`;
      }
    }
    out += escapeHtml(json.slice(lastIndex));

    // Build real nodes from the (safely escaped) highlighted markup.
    const tpl = document.createElement("template");
    tpl.innerHTML = out;
    return tpl.content.cloneNode(true);
  }

  private _onActiveItemChanged(
    e: GridActiveItemChangedEvent<JsonRecord>,
  ): void {
    const item = e.detail.value;
    this._openItems = item ? [item] : [];
    this.requestUpdate();
  }
}

customElements.define("json-table", JsonTable);
