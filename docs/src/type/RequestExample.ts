import type { HarRequest } from "httpsnippet";

export interface RequestExample {
  name: string | null;
  har: Partial<HarRequest>;
}
