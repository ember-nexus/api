// Astro integration wrapping pagefind's indexer, in place of `astro-pagefind`'s
// default export.
//
// `astro-pagefind` derives every result's URL from each HTML file's on-disk path
// via pagefind's own `index.addDirectory()`. `build.format` here is `'directory'`
// (the default), so every page is emitted as `<route>/index.html`, and pagefind's
// path-derived URL always keeps the trailing slash (`/reference/search/foo/`).
// This repo's canonical route URLs never have one (see `pageRouteParam` /
// `pagePath` in `lib/routes.ts`, and the `[...slug].astro` route they feed), so
// search results linking to the slash variant is a drift between two URL schemes,
// not a cosmetic issue — see the trailing-slash redirect in `tools/Caddyfile` and
// `trailingSlash: 'never'` in `astro.config.mjs`.
//
// `addDirectory` has no option to override the derived URL, so we walk the output
// ourselves and index each file with `index.addHTMLFile()`, passing an explicit,
// slash-free `url` instead. Content parsing (`data-pagefind-body`,
// `data-pagefind-ignore`, language detection, ...) is unaffected — that all
// happens inside pagefind itself from the HTML `content` we hand it, exactly as
// it would if `addDirectory` had read the same file. Dev-time serving of
// `/pagefind/*` is unchanged, copied from `astro-pagefind`.
import { readFile, readdir } from "node:fs/promises";
import path from "node:path";
import { fileURLToPath } from "node:url";

import type { AstroIntegration } from "astro";
import { type PagefindServiceConfig, createIndex } from "pagefind";
import sirv from "sirv";

export interface PagefindOptions {
  /** `PagefindServiceConfig` passed to pagefind's `createIndex` */
  indexConfig?: PagefindServiceConfig;
}

/**
 * Recursively collects every `*.html` file under `dir`, as paths relative to
 * `root`. Skips `pagefind/`, the output directory this same build is about to
 * write its index into (irrelevant on a clean build — it doesn't exist yet —
 * but matters when re-running against a stale `dist/`).
 */
async function collectHtmlFiles(
  root: string,
  dir: string = root,
): Promise<string[]> {
  const entries = await readdir(dir, { withFileTypes: true });
  const files: string[] = [];
  for (const entry of entries) {
    const abs = path.join(dir, entry.name);
    if (entry.isDirectory()) {
      if (path.relative(root, abs) === "pagefind") continue;
      files.push(...(await collectHtmlFiles(root, abs)));
    } else if (entry.isFile() && entry.name.endsWith(".html")) {
      files.push(path.relative(root, abs));
    }
  }
  return files;
}

/**
 * Mirrors this repo's route convention (`lib/routes.ts`): every page URL is
 * slash-free except the root. `foo/index.html` -> `/foo`, `index.html` -> `/`,
 * `foo.html` -> `/foo` (the flat form Astro uses for reserved routes like 404).
 */
function urlForOutputFile(relPath: string): string {
  const posixPath = relPath.split(path.sep).join("/");
  if (posixPath === "index.html") return "/";
  const stripped = posixPath.endsWith("/index.html")
    ? posixPath.slice(0, -"/index.html".length)
    : posixPath.replace(/\.html$/, "");
  return "/" + stripped;
}

export default function pagefind({
  indexConfig,
}: PagefindOptions = {}): AstroIntegration {
  let clientDir: string | undefined;
  return {
    name: "pagefind",
    hooks: {
      "astro:config:setup": ({ config, logger }): void => {
        if (config.output === "server") {
          logger.warn(
            "Output type `server` does not produce static *.html pages in its output and thus will not work with this pagefind integration.",
          );
        }
        if (config.adapter) {
          clientDir = fileURLToPath(config.build.client);
        }
      },
      "astro:server:setup": ({ server, logger }): void => {
        const outDir =
          clientDir ??
          path.join(server.config.root, server.config.build.outDir);
        logger.debug(`Serving pagefind from ${outDir}`);
        const serve = sirv(outDir, {
          dev: true,
          etag: true,
        });
        server.middlewares.use((req, res, next) => {
          if (req.url?.startsWith("/pagefind/")) {
            serve(req, res, next);
          } else {
            next();
          }
        });
      },
      "astro:build:done": async ({ dir, logger }): Promise<void> => {
        const outDir = fileURLToPath(dir);
        const { index, errors: createErrors } = await createIndex(indexConfig);
        if (!index) {
          logger.error("Pagefind failed to create index");
          createErrors.forEach(logger.error);
          return;
        }
        const htmlFiles = await collectHtmlFiles(outDir);
        for (const relPath of htmlFiles) {
          const content = await readFile(path.join(outDir, relPath), "utf-8");
          const { errors: addErrors } = await index.addHTMLFile({
            sourcePath: relPath,
            url: urlForOutputFile(relPath),
            content,
          });
          if (addErrors.length) {
            logger.error(`Pagefind failed to index ${relPath}`);
            addErrors.forEach(logger.error);
            return;
          }
        }
        logger.info(`Pagefind indexed ${htmlFiles.length} pages`);
        const { outputPath, errors: writeErrors } = await index.writeFiles({
          outputPath: path.join(outDir, "pagefind"),
        });
        if (writeErrors.length) {
          logger.error("Pagefind failed to write index");
          writeErrors.forEach(logger.error);
          return;
        } else {
          logger.info(`Pagefind wrote index to ${outputPath}`);
        }
      },
    },
  };
}
