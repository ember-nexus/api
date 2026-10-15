# CLAUDE.md

## Stack

Astro (static output) + MDX content collections, built with `pnpm` inside a Docker Compose
container, orchestrated via the repo's root `taskfile.yml` (`docs:*` tasks). App root is `docs/`
itself; the container-facing tooling (Dockerfile, Caddyfile) lives in `docker/docs/` at the repo
root, one level up. The `docs` service itself is defined in the repo's single dev compose file,
`tools/docker-compose.yml`, alongside the `api` service and the backing databases.

## Running commands

The `docs` container is normally already up (`task dev:up`). Run project commands inside it via
`docker compose -f tools/docker-compose.yml exec`.

**Always pass `--user astro`.** The container's default exec user is `root` (uid 0); the host bind
mount (`../docs:/docs`) is meant to be owned by `astro` (uid 1000, set up in `docker/docs/Dockerfile`).
Running `pnpm install` / `pnpm run dev` / `pnpm run build` etc. as root writes root-owned files
into `node_modules/.vite`, `dist`, and similar caches — the next `astro dev` run as `astro` then
fails with `EACCES: permission denied, unlink ...` because it can't clean up files it doesn't own.

```bash
docker compose -f tools/docker-compose.yml exec --user astro docs sh -c "cd /docs && pnpm run build"
```

`task docs:dev:cli` opens an interactive shell in the container already as `--user astro` — prefer
that for anything interactive.

If root-owned files already leaked in (symptom: `EACCES ... unlink` on `pnpm run dev`), fix with:

```bash
docker compose -f tools/docker-compose.yml exec docs sh -c "chown -R astro:astro /docs"
```

## Taskfile shortcuts

- `task dev:build` / `task dev:up` / `task dev:down` — build/start/stop the whole dev stack (api,
  docs, databases, ...); there's no docs-only variant, the `docs` service just rides along
- `task docs:dev:cli` — shell into the `docs` container as `astro`
- `task docs:check:all` — build, test, lint, spellcheck, e2e, in dependency order
- `task docs:prod:build` / `task docs:prod:run` — build/run the production image (Caddy serving `dist/`)
- `task docs:swagger:generate` — regenerate `docs/src/data/swagger.json` from `docs/src/data/swagger/**/*.json`
  (runs `docs/scripts/generate-swagger.mjs` in the dev container). Fragments can embed an
  `x-ember-nexus-recording` marker (a path relative to `tests/recordings/`) instead of a literal
  example value/HAR body; the script injects that file's real, current content, so examples track
  the actual recorded HTTP traffic instead of a hand-copied duplicate. CI
  (`docs-swagger-up-to-date` in `ci-test.yml`) regenerates and hashes the output to fail if
  someone edited a fragment, or a recording changed, without regenerating.

## Content model

Every page is authored once as `.mdx` under `docs/src/data/{pages,endpoints,commands}` and rendered
twice: as HTML (`src/pages/**/*.astro`) and as CommonMark/GFM markdown (`src/pages/**/*.md.ts`, one
route per HTML route) via a custom mdast JSX runtime (`src/mdmx/`). Every component used inside MDX
needs **two** implementations kept in sync — an `.astro` component (`src/component/`) and a
markdown-node function (`src/mdmx/registry/` or `src/mdmx/cards/`) — this is the most common place
for the two render targets to silently drift, so check both when touching content components.

`llms.txt` (`src/pages/llms.txt.ts`) indexes every markdown page; every page's markdown twin ends
with a footer nav (Home / site map / prev / next) for LLM crawling.

### Linking to an endpoint

Every in-content link to an endpoint page follows one schema: link text is the method and URL
template in backticks, `` `METHOD /path` `` (e.g. `` [`PATCH /upload/<uuid>`](/api/patch-upload) ``),
never the bare method name alone (`` [`PATCH`](...) ``) or the bare path. The link target is
`/api/<endpoint>`, where `<endpoint>` is that page's `endpoint:` frontmatter value. Keep this even
when two links to different endpoints sit right next to each other (e.g. "`POST`/`PUT`" listing both
verbs for the same path) — write out both full links rather than collapsing to one bare method.

## Before considering a change done

- `pnpm run build` — must succeed; check `dist/**/*.md` as well as `dist/**/*.html` for anything
  content-related, not just the HTML.
- `pnpm run test` — vitest quality suite (swagger status codes, curl formatting, markdown
  validity/no leaked `html` mdast nodes, external link whitelist).
- `pnpm run cs` — eslint + prettier. The repo currently has pre-existing lint noise (missing
  `.ts` import extensions, some `any` usage) — don't chase those down, just don't add new ones.
