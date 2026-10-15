export interface Schema {
  /** URL-safe slug used for routing, e.g. `"element-id"`. */
  id: string;
  /** Schema name as declared under `components.schemas`, e.g. `"ElementId"`. */
  name: string;
  /** The raw OpenAPI schema object, kept untyped on purpose. */
  schema: unknown;
}
