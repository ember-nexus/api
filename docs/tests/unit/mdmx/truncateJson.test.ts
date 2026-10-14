import { describe, expect, it } from 'vitest';

import { truncateJson } from '../../../src/mdmx/truncateJson';

describe('truncateJson', () => {
    it('returns small values unchanged (default size budget)', () => {
        const value = { type: 'Plant', id: '1', data: { name: 'Rose' } };
        expect(truncateJson(value)).toEqual(value);
    });

    it('returns a small array unchanged even with a low maxArrayItems', () => {
        const value = [1, 2, 3];
        // sizeBudget defaults to 2000, so this never enters truncation at all.
        expect(truncateJson(value, { maxArrayItems: 2 })).toEqual(value);
    });

    it('does not mutate the input', () => {
        const value = { list: Array.from({ length: 20 }, (_, i) => ({ type: 'X', i })) };
        const before = JSON.stringify(value);
        truncateJson(value, { sizeBudget: 10 });
        expect(JSON.stringify(value)).toBe(before);
    });

    it('truncates long leaf strings with a trailing "..." (three ASCII dots)', () => {
        const value = { description: 'a'.repeat(500) };
        const result = truncateJson(value, { sizeBudget: 10, maxStringLength: 20 }) as {
            description: string;
        };
        expect(result.description).toBe(`${'a'.repeat(20)}...`);
        expect(result.description.endsWith('...')).toBe(true);
        expect(result.description).not.toContain('…'); // not the unicode "…"
    });

    it('leaves short strings untouched even past the size budget', () => {
        const value = { a: 'short', list: Array.from({ length: 20 }, (_, i) => i) };
        const result = truncateJson(value, { sizeBudget: 10, maxStringLength: 5 }) as { a: string };
        expect(result.a).toBe('short');
    });

    it('caps oversized arrays at maxArrayItems (including the omission marker)', () => {
        const value = Array.from({ length: 50 }, (_, i) => ({ type: 'Same', i }));
        const result = truncateJson(value, { sizeBudget: 10, maxArrayItems: 4 }) as unknown[];
        expect(result).toHaveLength(4);
    });

    it('keeps one representative of every distinct shape before padding', () => {
        const value = [
            { type: 'Plant', i: 0 },
            { type: 'Plant', i: 1 },
            { type: 'Plant', i: 2 },
            { type: 'Taxon', i: 3 },
            { type: 'IS_MEMBER_OF', i: 4 },
        ];
        // maxArrayItems: 4 -> 1 marker slot reserved, leaving room for exactly
        // the 3 distinct shapes (Plant, Taxon, IS_MEMBER_OF) as representatives.
        const result = truncateJson(value, { sizeBudget: 10, maxArrayItems: 4 }) as unknown[];

        const types = result.slice(0, 3).map((item: any) => item.type);
        expect(types).toEqual(['Plant', 'Taxon', 'IS_MEMBER_OF']);
        expect(typeof result[3]).toBe('string');
        expect(result[3] as string).toContain('2 more items omitted');
    });

    it('pads with subsequent items when there are fewer distinct shapes than the cap', () => {
        const value = Array.from({ length: 10 }, (_, i) => ({ type: 'Only', i }));
        const result = truncateJson(value, { sizeBudget: 10, maxArrayItems: 4 }) as unknown[];

        // 1 distinct shape -> 1 representative + 2 padding items + 1 omission marker.
        expect(result).toHaveLength(4);
        expect(typeof result[3]).toBe('string');
        expect(result[3] as string).toContain('7 more items omitted');
    });

    it('preserves original relative order of kept items', () => {
        const value = [
            { type: 'A', i: 0 },
            { type: 'B', i: 1 },
            { type: 'A', i: 2 },
            { type: 'B', i: 3 },
            { type: 'C', i: 4 },
        ];
        const result = truncateJson(value, { sizeBudget: 10, maxArrayItems: 3 }) as any[];
        const indices = result.filter((item) => typeof item !== 'string').map((item) => item.i);
        expect(indices).toEqual([...indices].sort((a, b) => a - b));
    });

    it('reports every distinct shape seen in the omission marker, not just the kept ones', () => {
        const value = [
            { type: 'A', i: 0 },
            { type: 'B', i: 1 },
            { type: 'C', i: 2 },
            { type: 'D', i: 3 },
        ];
        const result = truncateJson(value, { sizeBudget: 10, maxArrayItems: 2 }) as any[];
        const marker = result[result.length - 1] as string;
        expect(marker).toContain('A');
        expect(marker).toContain('B');
        expect(marker).toContain('C');
        expect(marker).toContain('D');
    });

    it('uses the full key set as a shape signature when there is no "type" field', () => {
        const value = [
            { a: 1, b: 2 },
            { a: 3, b: 4 }, // duplicate shape of the item above, gets omitted
            { c: 5, d: 6 },
            { c: 7, d: 8 }, // duplicate shape of the item above, gets omitted
        ];
        // maxArrayItems: 3 -> 1 marker slot reserved, leaving room for exactly
        // the 2 distinct shapes ("a,b" and "c,d") as representatives.
        const result = truncateJson(value, { sizeBudget: 10, maxArrayItems: 3 }) as any[];
        expect(result.slice(0, 2)).toEqual([
            { a: 1, b: 2 },
            { c: 5, d: 6 },
        ]);
        expect(typeof result[2]).toBe('string');
        expect(result[2] as string).toContain('2 more items omitted');
    });

    it('singularizes the omission marker for exactly one omitted item', () => {
        // Reserving a marker slot means a truncated array always omits at
        // least 2 items once maxArrayItems >= 1, so exercising the singular
        // wording needs the degenerate maxArrayItems: 0 case (capForItems: 0).
        const value = [{ type: 'Only', i: 0 }];
        const result = truncateJson(value, { sizeBudget: 10, maxArrayItems: 0 }) as unknown[];
        expect(result).toHaveLength(1);
        expect(result[0] as string).toContain('1 more item omitted');
        expect(result[0] as string).not.toContain('1 more items omitted');
    });

    it('never truncates a blacklisted key\'s string value, regardless of length', () => {
        const value = { query: 'x'.repeat(500), other: 'y'.repeat(500) };
        const result = truncateJson(value, { sizeBudget: 10, maxStringLength: 20 }) as {
            query: string;
            other: string;
        };
        expect(result.query).toBe('x'.repeat(500));
        expect(result.other).toBe(`${'y'.repeat(20)}...`);
    });

    it('never truncates a blacklisted key\'s object value, including nested arrays inside it', () => {
        const value = {
            query: {
                match: Array.from({ length: 50 }, (_, i) => ({ type: 'Clause', i, text: 'z'.repeat(500) })),
            },
        };
        const result = truncateJson(value, { sizeBudget: 10, maxArrayItems: 2, maxStringLength: 10 }) as {
            query: typeof value.query;
        };
        expect(result.query).toEqual(value.query);
    });

    it('never truncates a blacklisted key\'s array value', () => {
        const value = { query: Array.from({ length: 50 }, (_, i) => i) };
        const result = truncateJson(value, { sizeBudget: 10, maxArrayItems: 2 }) as { query: number[] };
        expect(result.query).toEqual(value.query);
    });

    it('only exempts the exact blacklisted key, not keys nested inside sibling values', () => {
        const value = { queryResults: Array.from({ length: 50 }, (_, i) => ({ type: 'X', i })) };
        const result = truncateJson(value, { sizeBudget: 10, maxArrayItems: 2 }) as {
            queryResults: unknown[];
        };
        expect(result.queryResults.length).toBeLessThanOrEqual(2);
    });

    it('supports a custom keyBlacklist that replaces the default', () => {
        const value = { query: 'q'.repeat(500), secret: 's'.repeat(500) };
        const result = truncateJson(value, {
            sizeBudget: 10,
            maxStringLength: 10,
            keyBlacklist: ['secret'],
        }) as { query: string; secret: string };
        expect(result.query).toBe(`${'q'.repeat(10)}...`); // no longer exempt
        expect(result.secret).toBe('s'.repeat(500)); // now exempt instead
    });

    it('recurses into nested objects and arrays', () => {
        const value = {
            debug: [{ type: 'Step', input: { long: 'x'.repeat(200) } }],
            results: Array.from({ length: 20 }, (_, i) => ({ type: 'Node', i, note: 'y'.repeat(200) })),
        };
        const result = truncateJson(value, { sizeBudget: 10, maxArrayItems: 3, maxStringLength: 10 }) as any;

        expect(result.debug[0].input.long).toBe(`${'x'.repeat(10)}...`);
        expect(result.results.length).toBeLessThanOrEqual(3);
        for (const item of result.results) {
            if (typeof item === 'string') continue;
            expect(item.note).toBe(`${'y'.repeat(10)}...`);
        }
    });
});
