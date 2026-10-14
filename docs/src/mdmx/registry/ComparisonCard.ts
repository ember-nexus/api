import type { RootContent } from "mdast";

type Variant = "good" | "bad" | "neutral";

interface Props {
  variant?: Variant;
  title: string;
  children: RootContent[];
}

const prefixes: Record<Variant, string> = {
  good: "✓ ",
  bad: "✗ ",
  neutral: "",
};

/** Mirrors the HTML card's icon + title header as a depth-4 heading. */
export function ComparisonCard(props: Props): RootContent[] {
  const prefix = prefixes[props.variant ?? "neutral"];
  const heading: RootContent = {
    type: "heading",
    depth: 4,
    children: [{ type: "text", value: `${prefix}${props.title}` }],
  };
  return [heading, ...props.children];
}
