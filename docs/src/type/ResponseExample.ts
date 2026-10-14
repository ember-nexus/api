import type { HttpStatusCode } from "./HttpStatusCode.ts";
import type { Link } from "./Link.ts";

export interface ResponseExample {
  httpStatusCode: HttpStatusCode;
  name: string | null;
  description: string;
  links: Link[];
  body:
    | {
        content: string;
        type: "plain" | "json";
      }
    | {
        type: "binary";
      }
    | null;
  headers: string;
  /** Names only of `headers`, in the same order — for the markdown compact rendering. */
  headerNames: string[];
  schema: string | null;
  /**
   * Name of the `components.schemas` entry this response's schema is a bare
   * `$ref` to, in the *undereferenced* spec (`null` for an inline/anonymous
   * schema, or when no undereferenced spec was supplied). Used by the
   * markdown renderer to dedupe repeated named schemas (e.g. `Problem`)
   * across a page's responses instead of inlining them each time.
   */
  schemaRefName: string | null;
}
