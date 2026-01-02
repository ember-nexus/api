#!/bin/sh
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$SCRIPT_DIR"

TEMPLATE="../docs/open-api/swagger_template.json"
OUTPUT="../docs/open-api/swagger.json"

SCHEMAS=$(
  for f in $(ls ../docs/open-api/schemas/*.json | sort); do
    key=$(basename "$f" .json)
    jq -n --arg key "$key" --slurpfile val "$f" '{ ($key): $val[0] }'
  done | jq -s 'add'
)

EXAMPLES=$(
  for f in $(ls ../docs/open-api/examples/*.json | sort); do
    key=$(basename "$f" .json)
    jq -n --arg key "$key" --slurpfile val "$f" '{ ($key): $val[0] }'
  done | jq -s 'add'
)

PARAMETERS=$(
  for f in $(ls ../docs/open-api/parameters/*.json | sort); do
    key=$(basename "$f" .json)
    jq -n --arg key "$key" --slurpfile val "$f" '{ ($key): $val[0] }'
  done | jq -s 'add'
)

PATHS=$(
  for f in $(find ../docs/open-api/paths -mindepth 2 -maxdepth 2 -name "*.json" | sort); do
    tag=$(basename "$(dirname "$f")")
    jq --arg tag "$tag" '{(.path): {(.method): (.content + {tags: [$tag]})}}' "$f"
  done | jq -s '
    reduce .[] as $item (
      {};
      . * $item
    )
  '
)

jq \
  --argjson schemas "$SCHEMAS" \
  --argjson examples "$EXAMPLES" \
  --argjson parameters "$PARAMETERS" \
  --argjson paths "$PATHS" \
  '
    .components.schemas = $schemas |
    .components.examples = $examples |
    .components.parameters = $parameters |
    .paths = $paths
  ' \
  "$TEMPLATE" > "$OUTPUT"
