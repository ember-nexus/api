import type { Link } from "./Link.js";

/**
 * A non-header request parameter — i.e. one carried in the URL path or query
 * string (and, in principle, cookies). Header parameters are modelled
 * separately via `RequestHeader`.
 */
export interface RequestParameter {
  name: string;
  /** Where the parameter is transmitted. */
  location: "path" | "query" | "cookie";
  /**
   * Path parameters are always required per the OpenAPI spec; query/cookie
   * parameters may be optional.
   */
  presence: "optional" | "required";
  description: string;
  /** Schema type, e.g. `"string"` / `"integer"`. `null` when unspecified. */
  type: string | null;
  /** Schema format, e.g. `"uuid"`. `null` when unspecified. */
  format: string | null;
  /** Stringified default value, or `null` when none is declared. */
  defaultValue: string | null;
  /** Stringified example value, or `null` when none is declared. */
  example: string | null;
  /**
   * Human-readable schema constraints, e.g. `["≥ 1", "default 100"]`.
   * Pre-formatted so the component stays presentation-only.
   */
  constraints: string[];
  links: Link[];
}
