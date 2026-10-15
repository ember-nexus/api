import type { ExpressiveCodePlugin } from "@expressive-code/core";
import { definePlugin } from "astro-expressive-code";

export function translateNo(): ExpressiveCodePlugin {
  return definePlugin({
    name: "No Translate",
    hooks: {
      postprocessRenderedBlock: ({ renderData }) => {
        renderData.blockAst.properties = {
          ...renderData.blockAst.properties,
          translate: "no",
        };
      },
    },
  });
}
