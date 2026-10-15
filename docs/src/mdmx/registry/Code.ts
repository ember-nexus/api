import type { RootContent } from "mdast";

interface Props {
  code: string;
  lang?: string;
}

export function Code(props: Props): RootContent {
  return { type: "code", lang: props.lang ?? null, value: props.code };
}
