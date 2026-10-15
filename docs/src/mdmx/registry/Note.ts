import type { RootContent } from "mdast";

interface Props {
  title?: string;
  children: RootContent[];
}

/**
 * Not part of the task's reference table, but `Note` is used in real content
 * (`src/data/endpoints/01-element/07-post-element.mdx`) and would otherwise
 * hit the "Expected component X to be defined" failure on the markdown
 * target. Rendered as a blockquote with a bold warning label, the closest
 * CommonMark equivalent of the callout box.
 */
export function Note(props: Props): RootContent[] {
  const label: RootContent = {
    type: "paragraph",
    children: [
      {
        type: "strong",
        children: [{ type: "text", value: `⚠ ${props.title ?? "Note"}` }],
      },
    ],
  };
  return [{ type: "blockquote", children: [label, ...props.children] }];
}
