// inlineCodeAttrs.ts
//
// Adds attributes to *inline* `code` elements from a trailing meta block:
//
//   `some inline code {.abc}`   -> <code class="abc">some inline code</code>
//   `lookup {.token .mono}`     -> <code class="token mono">lookup</code>
//   `id {#anchor}`              -> <code id="anchor">id</code>
//   `x {.a data-kind=note}`     -> <code class="a" data-kind="note">x</code>
//
// The meta block must sit at the very end of the code text. Every token inside
// it must look like an attribute (`.class`, `#id`, or `key=val`); otherwise the
// block is left alone, so ordinary code like `{ foo: bar }` is never eaten.

import { visit } from "unist-util-visit";

// --- minimal local hast types (avoids depending on @types/hast) -------------

type PropertyValue =
  string | number | boolean | Array<string | number> | null | undefined;

type Properties = Record<string, PropertyValue>;

interface Text {
  type: "text";
  value: string;
}

interface Element {
  type: "element";
  tagName: string;
  properties: Properties;
  children: ElementContent[];
}

interface Root {
  type: "root";
  children: ElementContent[];
}

// Permissive catch-all: the real hast tree also contains comment / doctype /
// raw nodes, so this keeps it assignable to the minimal types above.
type ElementContent = Element | Text | { type: string; [key: string]: unknown };

// ---------------------------------------------------------------------------

// Trailing `{ ... }` (plus any whitespace before it), anchored to end-of-text.
const ATTR_RE = /\s*\{([^{}]+)\}\s*$/;

interface ParsedAttrs {
  classes: string[];
  id?: string;
  attrs: Record<string, string>;
}

function parseAttrs(raw: string): ParsedAttrs | null {
  const tokens = raw.trim().split(/\s+/).filter(Boolean);

  // Bail unless every token is a well-formed attr token. This is the guard that
  // stops real code ending in `{...}` from being misinterpreted as meta.
  const ok = tokens.every(
    (t) => t.startsWith(".") || t.startsWith("#") || t.includes("="),
  );
  if (!tokens.length || !ok) return null;

  const classes: string[] = [];
  const attrs: Record<string, string> = {};
  let id: string | undefined;

  for (const token of tokens) {
    if (token.startsWith(".")) {
      classes.push(token.slice(1));
    } else if (token.startsWith("#")) {
      id = token.slice(1);
    } else {
      const eq = token.indexOf("=");
      const key = token.slice(0, eq);
      const val = token.slice(eq + 1).replace(/^["']|["']$/g, ""); // strip quotes
      attrs[key] = val;
    }
  }

  return { classes, id, attrs };
}

// hast's className can be a string or an array; normalize to a string array.
function toClassArray(value: PropertyValue): string[] {
  if (!value) return [];
  if (Array.isArray(value)) return value.map(String);
  return String(value).split(/\s+/).filter(Boolean);
}

function applyAttrs(node: Element): void {
  const last = node.children[node.children.length - 1];
  if (!last || last.type !== "text") return;
  const text = last as Text;

  const match = text.value.match(ATTR_RE);
  if (!match) return;

  const parsed = parseAttrs(match[1]);
  if (!parsed) return; // didn't look like a real attr block — leave text intact

  // 1. strip the meta (and the whitespace before it) from the visible text
  text.value = text.value.slice(0, match.index ?? 0);

  // 2. merge into the element's properties, *appending* classes
  const classes = [
    ...toClassArray(node.properties.className),
    ...parsed.classes,
  ];

  node.properties = {
    ...node.properties,
    ...parsed.attrs,
    ...(parsed.id ? { id: parsed.id } : {}),
    ...(classes.length ? { className: classes } : {}),
  };
}

export function inlineCodeAttrs() {
  return (tree: Root): void => {
    visit(tree, "element", (node, _index, parent) => {
      if (node.tagName !== "code") return;
      // inline code only — skip `code` inside a `pre` (fenced/code blocks)
      if (parent?.type === "element" && parent.tagName === "pre") return;
      applyAttrs(node);
    });
  };
}
