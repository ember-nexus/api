import type { RootContent } from "mdast";

import { schemaPath } from "../../lib/routes.ts";
import { schemaParam } from "../../util/index.ts";
import { parseMarkdownSource } from "../markdownSource.ts";

// Markdown port of `SchemaPropertyList.astro` + `SchemaPropertyRow.astro`.
// The HTML version is a nested tree of cards; markdown has no equivalent
// layout primitive, so it becomes a nested bullet list instead — same
// information (name, type, required/optional, description, example,
// nested properties, oneOf/anyOf/allOf variants), same recursion.

type Obj = Record<string, unknown>;

const asObj = (v: unknown): Obj =>
  v !== null && v !== undefined && typeof v === "object" ? (v as Obj) : {};

function refName(ref: unknown): string | null {
  if (typeof ref !== "string") return null;
  const m = /#\/components\/schemas\/(.+)$/.exec(ref);
  return m ? m[1] : null;
}

function schemaLink(name: string, suffix = ""): RootContent[] {
  const nodes: RootContent[] = [
    {
      type: "link",
      url: schemaPath(schemaParam(name)),
      title: null,
      children: [{ type: "inlineCode", value: name }],
    },
  ];
  if (suffix) nodes.push({ type: "text", value: suffix });
  return nodes;
}

interface TypeDisplay {
  refName: string | null;
  suffix: string;
  plain: string | null;
  format: string | null;
}

function describeType(raw: unknown): TypeDisplay {
  const empty: TypeDisplay = {
    refName: null,
    suffix: "",
    plain: "—",
    format: null,
  };
  if (raw === null || raw === undefined || typeof raw !== "object")
    return empty;
  const r = raw as Obj;

  const direct = refName(r.$ref);
  if (direct) return { refName: direct, suffix: "", plain: null, format: null };

  if (r.type === "array" && r.items) {
    const inner = describeType(r.items);
    return {
      refName: inner.refName,
      suffix: "[]",
      plain: inner.refName ? null : (inner.plain ?? ""),
      format: null,
    };
  }

  if (Array.isArray(r.enum)) return { ...empty, plain: "enum" };

  const comp = (["oneOf", "anyOf", "allOf"] as const).find((k) =>
    Array.isArray(r[k]),
  );
  if (comp)
    return { ...empty, plain: `one of ${(r[comp] as unknown[]).length}` };

  if (typeof r.type === "string") {
    return {
      refName: null,
      suffix: "",
      plain: r.type,
      format: typeof r.format === "string" ? r.format : null,
    };
  }

  if (r.properties || r.additionalProperties)
    return { ...empty, plain: "object" };

  return empty;
}

// `propertyRow` and `propertyList`/`variantList` are mutually recursive
// (a property can itself be an object with nested properties) — there is no
// declaration order that satisfies `no-use-before-define` in both
// directions, so this file keeps the more natural top-down reading order.

function propertyRow(
  name: string,
  node: unknown,
  required: boolean,
): RootContent {
  const n = asObj(node);
  const type = describeType(node);

  const header: RootContent[] = [
    { type: "inlineCode", value: name },
    { type: "text", value: ": " },
  ];
  if (type.refName) {
    header.push(...schemaLink(type.refName, type.suffix));
  } else {
    header.push({
      type: "inlineCode",
      value: (type.plain ?? "") + type.suffix,
    });
  }
  if (type.format) header.push({ type: "text", value: ` (${type.format})` });
  header.push({
    type: "text",
    value: required ? " — required" : " — optional",
  });

  const block: RootContent[] = [{ type: "paragraph", children: header }];

  const description = typeof n.description === "string" ? n.description : null;
  if (description) block.push(...parseMarkdownSource(description));

  if (n.example !== undefined) {
    block.push({
      type: "paragraph",
      children: [
        { type: "text", value: "example: " },
        { type: "inlineCode", value: JSON.stringify(n.example) },
      ],
    });
  }

  // eslint-disable-next-line no-use-before-define -- mutual recursion, see note above propertyRow
  const nested = propertyList(n);
  // eslint-disable-next-line no-use-before-define -- mutual recursion, see note above propertyRow
  const variants = variantList(n);
  const sub = [...nested, ...variants];

  return {
    type: "listItem",
    checked: null,
    spread: false,
    children:
      sub.length > 0
        ? [
            ...block,
            { type: "list", ordered: false, start: null, children: sub },
          ]
        : block,
  };
}

function propertyList(schema: Obj): RootContent[] {
  if (
    schema.type !== "object" ||
    !schema.properties ||
    typeof schema.properties !== "object"
  )
    return [];
  const required = new Set<string>(
    Array.isArray(schema.required) ? (schema.required as string[]) : [],
  );
  return Object.entries(schema.properties as Obj).map(([name, node]) =>
    propertyRow(name, node, required.has(name)),
  );
}

function variantList(schema: Obj): RootContent[] {
  const comp = (["oneOf", "anyOf", "allOf"] as const).find((k) =>
    Array.isArray(schema[k]),
  );
  if (!comp || comp === "allOf") return [];
  const names = (schema[comp] as unknown[])
    .map((entry) => refName((entry as Obj)?.$ref))
    .filter((v): v is string => v !== null);
  return names.map((name) => ({
    type: "listItem",
    checked: null,
    spread: false,
    children: [
      {
        type: "paragraph",
        children: [{ type: "text", value: `${comp}: ` }, ...schemaLink(name)],
      },
    ],
  }));
}

export function schemaPropertyList(schema: unknown): RootContent[] {
  const raw = asObj(schema);
  const requiredNames = new Set<string>(
    Array.isArray(raw.required) ? (raw.required as string[]) : [],
  );
  const properties =
    raw.type === "object" &&
    raw.properties &&
    typeof raw.properties === "object"
      ? Object.entries(raw.properties as Obj)
      : [];

  const composition = (["oneOf", "anyOf", "allOf"] as const).find((k) =>
    Array.isArray(raw[k]),
  );

  const extendsRefs: string[] = [];
  const allOfProperties: [string, unknown][] = [];
  const allOfRequired = new Set<string>();
  if (composition === "allOf") {
    for (const entry of raw.allOf as unknown[]) {
      const e = entry as Obj;
      const name = refName(e.$ref);
      if (name) {
        extendsRefs.push(name);
      } else if (e.type === "object" || e.properties) {
        if (Array.isArray(e.required))
          for (const r of e.required as string[]) allOfRequired.add(r);
        if (e.properties && typeof e.properties === "object") {
          allOfProperties.push(...Object.entries(e.properties as Obj));
        }
      }
    }
  }

  const mergedProperties = [...properties, ...allOfProperties];
  const mergedRequired = new Set([...requiredNames, ...allOfRequired]);
  const variants = variantList(raw);

  const nodes: RootContent[] = [];

  if (extendsRefs.length > 0) {
    const children: RootContent[] = [];
    extendsRefs.forEach((name, i) => {
      if (i > 0) children.push({ type: "text", value: ", " });
      children.push(...schemaLink(name));
    });
    nodes.push({
      type: "paragraph",
      children: [{ type: "text", value: "Extends: " }, ...children],
    });
  }

  if (mergedProperties.length > 0) {
    nodes.push({
      type: "list",
      ordered: false,
      start: null,
      children: mergedProperties.map(([name, node]) =>
        propertyRow(name, node, mergedRequired.has(name)),
      ),
    });
  }

  if (variants.length > 0) {
    nodes.push({
      type: "paragraph",
      children: [
        {
          type: "text",
          value: `${composition} of — each has its own schema page:`,
        },
      ],
    });
    nodes.push({
      type: "list",
      ordered: false,
      start: null,
      children: variants,
    });
  }

  return nodes;
}
