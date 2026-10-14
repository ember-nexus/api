/// <reference types="vitest/config" />
// Routes vitest through Astro's own Vite pipeline (astro.config.mjs), so
// tests can import `astro:content` and exercise the `?md` markdown
// compilation exactly as the real build does — not a re-implementation of it.
import { getViteConfig } from 'astro/config';

export default getViteConfig({
    test: {
        include: ['tests/**/*.test.ts'],
    },
});
