// Phase 7 of the markdown render target (task.md §10): exercises the actual
// `?md` compilation pipeline through Astro's own Vite config (see
// vitest.config.ts), not a re-implementation of it.
import { getCollection } from 'astro:content';
import remarkParse from 'remark-parse';
import { unified } from 'unified';
import { describe, expect, it } from 'vitest';

import { prime } from '../../../src/lib';
import { hasMarkdownModule, renderMd } from '../../../src/mdmx';
import { serialize } from '../../../src/mdmx/serialize';
import * as endpointRoute from '../../../src/pages/api/[endpoint].md';
import * as commandRoute from '../../../src/pages/command/[command].md';
import * as pageRoute from '../../../src/pages/[...slug].md';
import * as schemaRoute from '../../../src/pages/openapi-schema/[schema].md';

await prime();

const [pages, endpoints, commands] = await Promise.all([
    getCollection('pages'),
    getCollection('endpoints'),
    getCollection('commands'),
]);

const [endpointPaths, commandPaths, schemaPaths] = await Promise.all([
    endpointRoute.getStaticPaths(),
    commandRoute.getStaticPaths(),
    schemaRoute.getStaticPaths(),
]);

describe('every content entry resolves to a ?md module', () => {
    it.each([...pages, ...endpoints, ...commands])('$id', (entry) => {
        expect(hasMarkdownModule(entry), entry.id).toBe(true);
    });
});

function assertNoLeakedHtml(md: string, label: string): void {
    const parser = unified().use(remarkParse);
    let tree: ReturnType<typeof parser.parse>;
    expect(() => {
        tree = parser.parse(md);
    }, `remark-parse threw for ${label}`).not.toThrow();

    const htmlNodes: unknown[] = [];
    const visit = (node: any): void => {
        if (node.type === 'html') htmlNodes.push(node);
        for (const child of node.children ?? []) visit(child);
    };
    visit(tree!);

    expect(htmlNodes, `leaked html node(s) in ${label}: ${JSON.stringify(htmlNodes)}`).toHaveLength(0);
}

describe('generated markdown round-trips through remark with no leaked html', () => {
    it.each(pages)('page $id', async (entry) => {
        const body = await renderMd(entry);
        const md = serialize([
            { type: 'heading', depth: 1, children: [{ type: 'text', value: entry.data.title }] },
            ...body,
        ]);
        assertNoLeakedHtml(md, entry.id);
    });

    // Runs the real route modules end to end — the swagger-derived cards
    // (RequestCard, ResponseCard, ...) only exist there, not in `mdComponents`.
    it.each(endpointPaths)('endpoint $params.endpoint', async ({ props }) => {
        const response = await endpointRoute.GET({ props } as never);
        assertNoLeakedHtml(await response.text(), String(props.entry.id));
    });

    it.each(commandPaths)('command $params.command', async ({ props }) => {
        const response = await commandRoute.GET({ props } as never);
        assertNoLeakedHtml(await response.text(), String(props.entry.id));
    });

    it.each(schemaPaths)('schema $params.schema', async ({ props }) => {
        const response = await schemaRoute.GET({ props } as never);
        assertNoLeakedHtml(await response.text(), String(props.schema.id));
    });
});

describe('markdown snapshots', () => {
    const fixtures = [
        'index',
        '03-reference/03-search/01-elasticsearch-query-dsl-mixin',
        '03-reference/03-search/01-elasticsearch-query-dsl-mixin/01-full-text-search',
    ];

    it.each(fixtures)('%s', async (id) => {
        const entry = pages.find((e) => e.id === id);
        if (!entry) throw new Error(`fixture page not found: ${id}`);

        const body = await renderMd(entry);
        const md = serialize([
            { type: 'heading', depth: 1, children: [{ type: 'text', value: entry.data.title }] },
            ...body,
        ]);

        await expect(md).toMatchSnapshot();
    });
});

describe('markdown routes are reachable via the real route modules', () => {
    it('index page resolves to the literal "index" param', async () => {
        const paths = await pageRoute.getStaticPaths();
        const indexPath = paths.find((p) => p.props.entry.id === 'index');
        expect(indexPath?.params.slug).toBe('index');
    });
});
