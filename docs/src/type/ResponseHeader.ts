import type { Link } from "./Link.js";

export interface ResponseHeader {
  header: string;
  presence: "optional" | "always";
  important: boolean;
  description: string;
  /** Example value from the header's schema, if any (`""` when absent). */
  example: string;
  links: Link[];
}
