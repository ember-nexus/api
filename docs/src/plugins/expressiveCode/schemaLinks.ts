import type {
  AnnotationBaseOptions,
  AnnotationRenderOptions,
  ExpressiveCodeHookContext,
  ExpressiveCodePlugin,
} from "@expressive-code/core";
import { ExpressiveCodeAnnotation, definePlugin } from "astro-expressive-code";
import { h } from "astro-expressive-code/hast";
import type { Parents } from "hast";

/**
 * Converts a PascalCase/camelCase schema name to the URL slug used by the
 * OpenAPI schema pages, e.g. "ElementId" -> "element-id".
 * Must stay in sync with schemaParam() in src/util/SwaggerUtil.ts.
 */
function schemaParam(name: string): string {
  return name
    .replace(/([a-z0-9])([A-Z])/g, "$1-$2")
    .replace(/[^a-zA-Z0-9]+/g, "-")
    .toLowerCase()
    .replace(/^-+|-+$/g, "");
}

/**
 * Annotation that wraps rendered HAST nodes in an <a> element linking to the
 * schema page for the matched ref.
 */
class SchemaRefAnnotation extends ExpressiveCodeAnnotation {
  href: string;

  constructor({ href, ...rest }: { href: string } & AnnotationBaseOptions) {
    super(rest);
    this.href = href;
  }

  render({ nodesToTransform }: AnnotationRenderOptions): Parents[] {
    return nodesToTransform.map((node) =>
      h("a", { href: this.href, class: "ec-schema-ref" }, [node]),
    );
  }
}

// Matches the schema name inside a JSON string value like:
//   "#/components/schemas/ElementId"
// Captures the schema name (group 1) and records the offset of the name
// within the full line so we can build a precise inlineRange.
const SCHEMA_REF_RE = /#\/components\/schemas\/([A-Za-z0-9_]+)/g;

export function schemaLinks(): ExpressiveCodePlugin {
  return definePlugin({
    name: "Schema Links",

    baseStyles: `
      a.ec-schema-ref {
        color: inherit;
        text-decoration-line: underline;
        text-decoration-style: dotted;
        text-underline-offset: 2px;
        cursor: pointer;
        &:hover {
          text-decoration-style: solid;
        }
        /* prevent child syntax-highlight spans from overriding styles */
        & span {
          color: inherit;
        }
      }
    `,

    hooks: {
      annotateCode({ codeBlock }: ExpressiveCodeHookContext): void {
        // Only process JSON code blocks.
        if (codeBlock.language !== "json") return;

        codeBlock.getLines().forEach((line) => {
          const text = line.text;
          SCHEMA_REF_RE.lastIndex = 0;

          let match;
          while ((match = SCHEMA_REF_RE.exec(text)) !== null) {
            const schemaName = match[1];
            // Must stay in sync with schemaPath() in src/lib/routes.ts.
            const href = `/openapi-schema/${schemaParam(schemaName)}`;

            // Annotate the full matched string, e.g. "#/components/schemas/ElementId"
            const columnStart = match.index;
            const columnEnd = match.index + match[0].length;

            line.addAnnotation(
              new SchemaRefAnnotation({
                href,
                inlineRange: { columnStart, columnEnd },
              }),
            );
          }
        });
      },
    },
  });
}
