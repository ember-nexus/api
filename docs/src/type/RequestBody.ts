/**
 * A single content-type variant of a request body, e.g. the
 * `application/json` entry. The component renders one of these per content
 * type, so every possible body is shown at once without a content-type
 * switcher.
 */
export interface RequestBodyContent {
  /** MIME type, e.g. `"application/json"`. */
  mimeType: string;
  /** Pretty-printed JSON schema for this content type, or `null`. */
  schema: string | null;
  /** Representative example body for this content type, or `null`. */
  example: {
    content: string;
    type: "plain" | "json";
  } | null;
}

export interface RequestBody {
  /** Whether the body must be sent. */
  required: boolean;
  /** Operation-level body description (markdown). */
  description: string;
  /** One entry per declared content type. */
  contents: RequestBodyContent[];
}
