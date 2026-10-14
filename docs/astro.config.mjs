// @ts-check
import { defineConfig } from 'astro/config';
import { copyFileSync, mkdirSync } from 'fs';
import { resolve } from 'path';

import tailwindcss from '@tailwindcss/vite';

/**
 * Vite plugin: copies src/data/swagger.json → public/swagger.json so the
 * file is served as a static asset and survives the production build.
 */
function copySwaggerPlugin() {
  return {
    name: 'copy-swagger',
    buildStart() {
      const src  = resolve('./src/data/swagger.json');
      const dest = resolve('./public/swagger.json');
      try {
        mkdirSync(resolve('./public'), { recursive: true });
        copyFileSync(src, dest);
      } catch (e) {
        console.warn('[copy-swagger] Could not copy swagger.json:', e.message);
      }
    },
  };
}

import mdx from '@astrojs/mdx';
import sitemap from '@astrojs/sitemap';

import alpinejs from '@astrojs/alpinejs';

import expressiveCode from 'astro-expressive-code';

import icon from 'astro-icon';
import {footnoteBackrefAugmentation, httpMethodAugmentation, inlineCodeAttrs, linkAugmentation} from "./src/plugins/rehype";
import mdRobotsHeaders from "./src/plugins/mdRobotsHeaders.ts";
import pagefind from "./src/plugins/pagefind.ts";
import trailingSlashRedirect from "./src/plugins/trailingSlashRedirect.ts";

import mdxRollup from '@mdx-js/rollup';
import remarkFrontmatter from 'remark-frontmatter';
import remarkGfm from 'remark-gfm';

/**
 * Second MDX compilation, for the markdown render target (`<route>.md`).
 *
 * @astrojs/mdx's own vite plugin filters on the exact id `/\.mdx$/`, so a
 * query-suffixed id like `foo.mdx?md` never reaches it and is free for this
 * plugin to claim instead — see fact 2 in task.md.
 *
 * @mdx-js/rollup can't be handed `include: /\.mdx\?md$/` directly: its
 * `transform` hook does `id.split('?')[0]` *before* running the `include`
 * filter (see node_modules/@mdx-js/rollup/lib/index.js), so the filter only
 * ever sees the query-less path and a query-based include can never match.
 * This thin wrapper gates on the raw id itself and calls the underlying
 * compiler directly, bypassing that filter entirely.
 */
function mdxMarkdownPlugin() {
  const compiler = mdxRollup({
    jsxImportSource: 'mdmx',
    elementAttributeNameCase: 'react',
    remarkPlugins: [remarkFrontmatter, remarkGfm],
    // Only inlineCodeAttrs: strips the `{.hl-1}`-style meta blob from the
    // inline code's own text (content, not styling) before the mdmx JSX
    // runtime sees it. No expressive-code, no link/http-method augmentation —
    // those only apply to HTML.
    rehypePlugins: [inlineCodeAttrs],
  });
  const isMdMdx = (id) => id.endsWith('.mdx?md');

  return {
    name: 'mdx-markdown-target',
    enforce: 'pre',
    async resolveId(source, importer, options) {
      if (!isMdMdx(source)) return null;
      // Force the query to survive resolution: resolve the bare path, then
      // re-attach `?md` to whatever id Vite's default resolver produced.
      const resolved = await this.resolve(source.slice(0, -3), importer, {
        ...options,
        skipSelf: true,
      });
      if (!resolved) return null;
      return `${resolved.id.split('?')[0]}?md`;
    },
    async transform(code, id) {
      if (!isMdMdx(id)) return null;
      return compiler.transform.call(this, code, id.slice(0, -3));
    },
  };
}

export default defineConfig({
  vite: {
    plugins: [tailwindcss(), copySwaggerPlugin(), mdxMarkdownPlugin()],
    resolve: {
      alias: {
        'mdmx/jsx-runtime': resolve('./src/mdmx/jsx-runtime.ts'),
        'mdmx/jsx-dev-runtime': resolve('./src/mdmx/jsx-runtime.ts'),
      },
    },
    build: {
      rollupOptions: {
        output: {
          // Split the heavy Lit/G6/Vaadin dependency tree into a dedicated
          // vendor chunk.  This chunk is only fetched on pages that actually
          // render <g6-graph> or <json-table> (via the lazy-loader in
          // Default.astro), and is cached across navigations separately from
          // the small per-page JS that every page needs.
          manualChunks(id) {
            if (
              id.includes('/node_modules/@antv/') ||
              id.includes('/node_modules/lit') ||
              id.includes('/node_modules/@lit/') ||
              id.includes('/node_modules/@vaadin/')
            ) {
              return 'vendor-lit-g6';
            }
          },
        },
      },
    },
  },
  build: {
    inlineStylesheets: 'never',
  },
  server: {
    host: true,
    // 'core' is the docker-compose service name — the `e2e` (Playwright) container reaches
    // the preview server as http://core:4322, so it needs to pass Astro/Vite's Host check too.
    allowedHosts: ['localhost', 'astro', 'ember-nexus-org-astro', 'core']
  },
  site: 'https://api.ember-nexus.dev',
  integrations: [
    trailingSlashRedirect(),
    mdRobotsHeaders(),
    expressiveCode(),
    mdx(),
    sitemap(),
    alpinejs(),
    icon(),
    pagefind(),
    (await import("@playform/compress")).default({
      CSS: false, // csso + lightningcss break Tailwind's responsive utilities (sm: prefixes)
    })
  ],
  markdown: {
    shikiConfig: {
      theme: 'min-light',
      langs: [
        "json",
        "text"
      ]
    },
    // These are picked up by both pipelines:
    // - @astrojs/mdx reads config.markdown.rehypePlugins via extendMarkdownConfig:true
    // - Astro's legacy migration moves them into the default processor for .md files
    // astro-expressive-code appends its own rehypeExpressiveCode plugin via the same
    // mechanism, so it always runs after our plugins. The deprecation warning about
    // this legacy field is an upstream issue in astro-expressive-code.
    rehypePlugins: [httpMethodAugmentation, linkAugmentation, inlineCodeAttrs, footnoteBackrefAugmentation],
  }
});
