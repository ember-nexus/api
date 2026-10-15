#!/usr/bin/env node
// `astro sync` writes the content layer's data store to node_modules/.astro/data-store.json
// (the build-mode cache path). vitest.config.ts runs vitest through `getViteConfig()`, which
// always starts Vite in "serve" (dev) mode - and in dev mode Astro reads the data store from
// <project root>/.astro/data-store.json instead, a file `astro sync`/`astro build` never write.
// On a checkout that never had a real `astro dev` session (every CI run, any fresh clone),
// that file doesn't exist, so `astro:content` collections resolve empty for `pnpm run test` -
// see gh-495. Running `astro sync` and copying its output to the dev-mode path closes that gap
// without needing a throwaway `astro dev` process.
import { execFileSync } from 'node:child_process';
import { copyFileSync, mkdirSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('..', import.meta.url));

execFileSync('npx', ['astro', 'sync'], { cwd: root, stdio: 'inherit' });

mkdirSync(new URL('.astro/', `file://${root}/`), { recursive: true });
copyFileSync(
    new URL('node_modules/.astro/data-store.json', `file://${root}/`),
    new URL('.astro/data-store.json', `file://${root}/`),
);
