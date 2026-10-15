import type { Link } from "./Link.js";

export interface RequestHeader {
  header: string;
  presence: "optional" | "required";
  description: string;
  links: Link[];
}
