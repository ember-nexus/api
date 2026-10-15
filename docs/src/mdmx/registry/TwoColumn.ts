import type { RootContent } from "mdast";

interface Props {
  children: RootContent[];
}

/** Purely a layout device in HTML; markdown has no columns, so just unwrap it. */
export function TwoColumn(props: Props): RootContent[] {
  return props.children;
}
