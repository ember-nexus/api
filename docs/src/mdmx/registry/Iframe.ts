import type { RootContent } from "mdast";

interface Props {
  src: string;
  title: string;
  height?: number;
}

/**
 * An `<iframe>` has no CommonMark equivalent. Content using `<Iframe>` is expected to follow it
 * with a plain markdown link to `src` (see e.g. the Cypher Path Subset grammar page) - that link
 * carries the markdown target here, so this just drops a short placeholder in its place.
 */
export function Iframe(props: Props): RootContent {
  return { type: "text", value: `[${props.title}]` };
}
