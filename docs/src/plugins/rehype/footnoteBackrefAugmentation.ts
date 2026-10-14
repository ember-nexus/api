import { h } from "hastscript";
import { visit } from "unist-util-visit";

// --- minimal local hast types (avoids depending on @types/hast) ------------

type Properties = Record<string, unknown>;

interface Element {
  type: "element";
  tagName: string;
  properties: Properties;
  children: ElementContent[];
}

interface Text {
  type: "text";
  value: string;
}

interface Root {
  type: "root";
  children: ElementContent[];
}

type ElementContent = Element | Text | { type: string; [key: string]: unknown };

// -----------------------------------------------------------------------

function isElement(
  node: ElementContent | undefined,
  tagName?: string,
): node is Element {
  return (
    node?.type === "element" &&
    (tagName === undefined || (node as Element).tagName === tagName)
  );
}

function isWhitespaceText(node: ElementContent): node is Text {
  return node.type === "text" && /^\s+$/.test((node as Text).value);
}

function isBackrefLink(node: ElementContent): node is Element {
  return (
    isElement(node, "a") &&
    (node as Element).properties?.dataFootnoteBackref !== undefined
  );
}

function makeCornerIcon(): ElementContent {
  return h(
    "svg",
    {
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 24 24",
      fill: "none",
      stroke: "currentColor",
      "stroke-width": "2",
      "stroke-linecap": "round",
      "stroke-linejoin": "round",
      class: "footnote-backref-icon",
      "aria-hidden": "true",
    },
    [
      h("path", { d: "M14 9 9 4 4 9" }),
      h("path", { d: "M20 20h-7a4 4 0 0 1-4-4V4" }),
    ],
  );
}

/**
 * Rewrites the backreference(s) mdast-util-to-hast appends to each footnote
 * definition (a "↩" per reference, "↩²" for a re-reference, run together
 * with plain spaces — see `footer.js` in `mdast-util-to-hast`) into:
 * an unlinked "corner-left-up" icon, followed by one plain numbered link per
 * reference site, separated by unlinked ", " — e.g. "↰ 1, 2". Each number
 * keeps the original link's `href`/`aria-label`, so it still jumps to the
 * exact reference that was clicked back to.
 *
 * HTML target only — the markdown twin drops backreferences entirely (see
 * the `a` intrinsic in `src/mdmx/jsx-runtime.ts`), since a plain CommonMark
 * list item has no `id` for them to point back to.
 */
export function footnoteBackrefAugmentation() {
  return (tree: Root): void => {
    visit(tree, "element", (node: Element) => {
      if (node.tagName !== "li") return;

      // `state.wrap()` (mdast-util-to-hast) appends a trailing "\n" text node
      // after the last real element for formatting, so the last *element*
      // child — not literally `children[children.length - 1]` — is the `<p>`
      // the backreferences were appended to.
      let tailIndex = node.children.length - 1;
      while (tailIndex >= 0 && !isElement(node.children[tailIndex]))
        tailIndex -= 1;
      const tail = node.children[tailIndex];
      if (!isElement(tail, "p")) return;

      const kids = tail.children;

      // Walk back over the trailing run of backref links, allowing a single
      // whitespace-only separator between consecutive links (exactly the
      // shape `footer.js` produces) but not past one.
      let start = kids.length;
      const backrefs: Element[] = [];
      while (start > 0 && isBackrefLink(kids[start - 1])) {
        backrefs.unshift(kids[start - 1] as Element);
        start -= 1;
        if (
          start > 1 &&
          isWhitespaceText(kids[start - 1]) &&
          isBackrefLink(kids[start - 2])
        ) {
          start -= 1;
        }
      }
      if (backrefs.length === 0) return;

      tail.children = kids.slice(0, start);
      const last = tail.children[tail.children.length - 1];
      if (last?.type === "text") {
        (last as Text).value = (last as Text).value.replace(/\s+$/, "");
      }

      const replacement: ElementContent[] = [
        { type: "text", value: " " },
        makeCornerIcon(),
        { type: "text", value: " " },
      ];
      backrefs.forEach((link, index) => {
        if (index > 0) replacement.push({ type: "text", value: ", " });
        replacement.push({
          ...link,
          children: [{ type: "text", value: String(index + 1) }],
        });
      });

      tail.children.push(...replacement);
    });
  };
}
