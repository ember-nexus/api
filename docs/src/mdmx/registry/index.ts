// The markdown render target's implicit component registry — the
// `mdmx`-side counterpart of `src/component/registry.html.ts`. Every
// exported function takes the same props its `.astro` twin does and returns
// mdast node(s) instead of rendering HTML.
import { Check } from "./Check.ts";
import { Code } from "./Code.ts";
import { CommandGroupList } from "./CommandGroupList.ts";
import { ComparisonCard } from "./ComparisonCard.ts";
import { EndpointGroupList } from "./EndpointGroupList.ts";
import { Iframe } from "./Iframe.ts";
import { Link } from "./Link.ts";
import { Missing } from "./Missing.ts";
import { Note } from "./Note.ts";
import { SchemaList } from "./SchemaList.ts";
import { SearchResponseCard } from "./SearchResponseCard.ts";
import { TwoColumn } from "./TwoColumn.ts";

export const mdComponents = {
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
