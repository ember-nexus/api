// src/util/renderMarkdown.ts
//
// Renders a markdown string with the same augmentations Astro applies to
// .md/.mdx files, using Astro's own (non-deprecated) processor API.
//
// IMPORTANT: @astrojs/markdown-remark must be a DECLARED dependency:
//     yarn add @astrojs/markdown-remark
// It is only a transitive dep of `astro`, so importing it here without
// declaring it fails to resolve (esp. in a yarn workspace) with
// "Cannot find module '@astrojs/markdown-remark'".

import { markdownConfigDefaults, unified } from "@astrojs/markdown-remark";

import {
  httpMethodAugmentation,
  inlineCodeAttrs,
  linkAugmentation,
} from "../plugins/rehype/index.ts";

type Renderer = Awaited<
  ReturnType<ReturnType<typeof unified>["createRenderer"]>
>;

// Build the renderer once, lazily, and reuse it across calls.
let rendererPromise: Promise<Renderer> | null = null;

function getRenderer(): Promise<Renderer> {
  if (!rendererPromise) {
    // The remark/rehype pipeline lives on the unified() processor (the new,
    // non-deprecated home for plugins). Order mirrors astro.config.mjs.
    const processor = unified({
      rehypePlugins: [
        httpMethodAugmentation,
        linkAugmentation,
        inlineCodeAttrs,
      ],
      // gfm and smartypants default to true here too. Set smartypants:false
      // if you don't want straight quotes/dashes turned "curly".
    });

    // createRenderer() takes the cross-cutting options (syntax highlighting,
    // images, ...). Start from Astro's defaults; add shikiConfig to match your
    // global theme if descriptions contain fenced code blocks.
    rendererPromise = processor.createRenderer({
      ...markdownConfigDefaults,
      // shikiConfig: { theme: "min-light", langs: ["json", "text"] },
    });
  }
  return rendererPromise;
}

/** Render a markdown string to an HTML string with the shared augmentations. */
export async function renderMarkdown(markdown: string): Promise<string> {
  if (!markdown) return "";
  const renderer = await getRenderer();
  const { code } = await renderer.render(markdown);
  return code;
}
