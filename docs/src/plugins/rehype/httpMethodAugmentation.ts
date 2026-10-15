// rehype-http-methods.js
import { h } from "hastscript";

// --- minimal local hast types (avoids depending on @types/hast) -------------

type Properties = Record<string, unknown>;

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

type Node = Root | ElementContent;

// -----------------------------------------------------------------------

const HTTP_METHODS = [
  "GET",
  "POST",
  "PUT",
  "DELETE",
  "PATCH",
  "HEAD",
  "OPTIONS",
];

function walk(node: Node, parent: Node | null): void {
  if (
    node.type === "element" &&
    (node as Element).tagName === "code" &&
    (parent as Element | null)?.tagName !== "pre"
  ) {
    const el = node as Element;
    const first = el.children?.[0] as Text | undefined;
    const text = first?.value?.trim();
    const firstWord = text?.split(/\s+/)[0];

    if (firstWord && HTTP_METHODS.includes(firstWord) && text) {
      const remainder = text.slice(firstWord.length).trim();

      if (!remainder) {
        // Only the method name — set prop directly on the code element
        el.properties = { ...el.properties, dataMethod: firstWord };
        el.children = [{ type: "text", value: firstWord }];
      } else {
        // Additional content — wrap method in span, keep remainder as text
        const methodSpan = h(
          "span",
          { dataMethod: firstWord },
          firstWord,
        ) as Element;
        const remainderText = text.slice(firstWord.length); // preserve original spacing
        el.children = [methodSpan, { type: "text", value: remainderText }];
      }
    }
  }

  if ("children" in node) node.children?.forEach((child) => walk(child, node));
}

export function httpMethodAugmentation() {
  return (tree: Root): void => {
    walk(tree, null);
  };
}
