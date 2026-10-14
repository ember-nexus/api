// Shrinks large JSON values for the markdown render target while preserving
// structural variety. Real API responses embedded in the docs (e.g. search
// results mixing nodes and relationships of different shapes) can run to
// hundreds of KB; dumping them verbatim into `.md` output is mostly noise for
// an LLM reader, which cares about *what shapes exist* far more than *how
// many of each*.
//
// Strategy:
//   - Arrays longer than `maxArrayItems` keep one representative item per
//     distinct "shape" (see `shapeOf`) first, then pad up to the cap with
//     the next items in original order, so the cap is still used even when
//     there are fewer distinct shapes than slots. A trailing string marker
//     reports how many items were omitted and which shapes were present.
//   - Leaf strings longer than `maxStringLength` are cut and suffixed with
//     `...` (three literal ASCII dots, not the single-codepoint "…" — cheaper
//     to encode and unambiguous in a fenced code block).
//   - Values whose full `JSON.stringify` size is already under `sizeBudget`
//     pass through completely unchanged, so small/already-concise examples
//     are never touched.
//   - Object properties whose key is in `keyBlacklist` are copied verbatim —
//     no string capping, no array shape-sampling, no recursion into them at
//     all, regardless of whether the value is a string or an object/array.
//     `query` is blacklisted by default: it's the search query itself (e.g.
//     an Elasticsearch DSL object or a Cypher string), which is exactly the
//     kind of content readers need in full, not sampled.
//
// Not used on the HTML path: `SearchResponseCard.astro` keeps rendering the
// full, untruncated response in its Graph/Table/Debug/Body tabs. This is a
// markdown-target-only concern.

export interface TruncateJsonOptions {
  /** Values whose JSON.stringify length is at or below this pass through unchanged. */
  sizeBudget?: number;
  /**
   * Max length of a truncated array's output, counting the trailing
   * omission marker as one slot. E.g. with maxArrayItems: 4, a truncated
   * array holds at most 3 real items plus 1 marker string.
   */
  maxArrayItems?: number;
  /** Leaf strings longer than this are cut, with a trailing "..." marker. */
  maxStringLength?: number;
  /**
   * Object keys whose values are always copied verbatim, untouched by any
   * truncation rule (string capping, array sampling, or recursion into
   * nested objects/arrays), no matter what type the value is.
   */
  keyBlacklist?: string[];
}

const DEFAULTS: Required<TruncateJsonOptions> = {
  sizeBudget: 2000,
  maxArrayItems: 4,
  maxStringLength: 100,
  keyBlacklist: ["query"],
};

/**
 * Groups array items by "shape" so truncation can keep one representative of
 * each shape instead of just the first N items, which can easily all be the
 * same shape (e.g. a run of relationships before the nodes they connect).
 */
function shapeOf(value: unknown): string {
  if (value === null || typeof value !== "object") return typeof value;
  if (Array.isArray(value)) return "array";
  const obj = value as Record<string, unknown>;
  if (typeof obj.type === "string") return `type:${obj.type}`;
  return Object.keys(obj).sort().join(",");
}

function truncateString(value: string, maxLength: number): string {
  if (value.length <= maxLength) return value;
  return `${value.slice(0, maxLength)}...`;
}

function truncateValue(
  value: unknown,
  opts: Required<TruncateJsonOptions>,
): unknown {
  if (typeof value === "string")
    return truncateString(value, opts.maxStringLength);
  // truncateValue/truncateArray are mutually recursive; one direction is
  // necessarily a forward reference. Safe: both are hoisted function
  // declarations, and neither is called until after the module has loaded.
  // eslint-disable-next-line no-use-before-define
  if (Array.isArray(value)) return truncateArray(value, opts);
  if (value !== null && typeof value === "object") {
    const result: Record<string, unknown> = {};
    for (const [key, val] of Object.entries(value as Record<string, unknown>)) {
      result[key] = opts.keyBlacklist.includes(key)
        ? val
        : truncateValue(val, opts);
    }
    return result;
  }
  return value;
}

function truncateArray(
  items: unknown[],
  opts: Required<TruncateJsonOptions>,
): unknown[] {
  if (items.length <= opts.maxArrayItems) {
    return items.map((item) => truncateValue(item, opts));
  }

  // Past this point some omission is unavoidable (items.length > maxArrayItems),
  // so a trailing marker will always be appended. Reserve one slot for it up
  // front so the *total* output length (kept items + marker) never exceeds
  // maxArrayItems.
  const capForItems = Math.max(0, opts.maxArrayItems - 1);

  // Pass 1: one representative per distinct shape, in original order.
  const shapesSeen = new Set<string>();
  const keptIndices: number[] = [];
  for (let i = 0; i < items.length && keptIndices.length < capForItems; i++) {
    const shape = shapeOf(items[i]);
    if (shapesSeen.has(shape)) continue;
    shapesSeen.add(shape);
    keptIndices.push(i);
  }

  // Pass 2: pad with the next not-yet-kept items so the cap is still used
  // even when there are fewer distinct shapes than slots.
  const keptSet = new Set(keptIndices);
  for (let i = 0; i < items.length && keptIndices.length < capForItems; i++) {
    if (keptSet.has(i)) continue;
    keptIndices.push(i);
    keptSet.add(i);
  }

  keptIndices.sort((a, b) => a - b);
  const omitted = items.length - keptIndices.length;
  const truncated = keptIndices.map((i) => truncateValue(items[i], opts));

  if (omitted > 0) {
    // Report the full set of distinct shapes present in the original array,
    // not just the ones that made the cut.
    const allShapes = [...new Set(items.map(shapeOf))].join(", ");
    truncated.push(
      `... ${omitted} more item${omitted === 1 ? "" : "s"} omitted (shapes seen: ${allShapes}) ...`,
    );
  }

  return truncated;
}

/**
 * Returns `value` unchanged if it's already small (per `sizeBudget`),
 * otherwise a structurally-representative, size-capped copy. Never mutates
 * the input.
 */
export function truncateJson(
  value: unknown,
  options: TruncateJsonOptions = {},
): unknown {
  const opts = { ...DEFAULTS, ...options };
  if (JSON.stringify(value).length <= opts.sizeBudget) return value;
  return truncateValue(value, opts);
}
