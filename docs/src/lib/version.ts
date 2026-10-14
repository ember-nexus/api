// The API version shown in the HTML sidebar (`Header.astro`) and, so LLM
// consumers of the markdown twins know which API version they're reading
// about, prepended to every markdown page and `llms.txt` (see
// `mdmx/headerMeta.ts`). Injected at build time via the `VERSION` env var
// (see `tools/docker-compose.yml` / `tools/Dockerfile`); falls back to `dev`
// for local builds where it isn't set.
export function apiVersion(): string {
  const version = import.meta.env.VERSION;
  return version && version !== "" ? version : "dev";
}
