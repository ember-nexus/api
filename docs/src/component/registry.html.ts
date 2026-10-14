// The HTML render target's implicit component registry.
//
// MDX content no longer imports components directly (see Phase 1 of the
// markdown-output changeset) — every page shell passes this map to
// `<Content components={htmlComponents} />` instead. `@astrojs/mdx` spreads
// it last into `_components`, so any capitalized tag in an .mdx file
// (`<Code>`, `<Link>`, ...) resolves through here.
//
// Keep this in sync with `src/mdmx/registry/index.ts`, the markdown-target
// counterpart — both are driven by the same set of tags used across the
// `.mdx` content files.
import { Code } from "astro-expressive-code/components";

import Check from "./Check.astro";
import CommandGroupList from "./CommandGroupList.astro";
import ComparisonCard from "./ComparisonCard.astro";
import EndpointGroupList from "./EndpointGroupList.astro";
import Iframe from "./Iframe.astro";
import Link from "./Link.astro";
import Missing from "./Missing.astro";
import Note from "./Note.astro";
import SchemaList from "./SchemaList.astro";
import SearchResponseCard from "./SearchResponseCard.astro";
import TwoColumn from "./TwoColumn.astro";

export const htmlComponents = {
  Check,
  Code,
  CommandGroupList,
  ComparisonCard,
  EndpointGroupList,
  Iframe,
  Link,
  Missing,
  Note,
  SchemaList,
  SearchResponseCard,
  TwoColumn,
};
