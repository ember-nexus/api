import { describe, it, expect } from 'vitest'
import { readdirSync, readFileSync } from 'fs'
import { join } from 'path'

// ── configuration ──

/**
 * Whitelist of allowed domains for external links.
 * Subdomains of a whitelisted domain are also allowed.
 * Leave empty to fail on every external link found.
 */
const DOMAIN_WHITELIST: string[] = [
    // Ember Nexus domains
    'reference-dataset.ember-nexus.dev',
    'ember-nexus.dev',
    
    // Ember Nexus related domains
    'github.com',
    'api.example.com',
    'hub.docker.com',
    'discord.gg',

    // docker-compose service name used in local example URLs (not a real external host)
    'minio',
    
    // technologies
    'neo4j.com',
    'www.elastic.co',
    'caddyserver.com',
    'traefik.io',
    'www.rabbitmq.com',
    'redis.io',
    'www.min.io',
    'aws.amazon.com',
    'www.mongodb.com',
    'securitytxt.org',
    'www.antlr.org',
    
    // external technologies
    'insomnia.rest',
    'www.postman.com',
    'swagger.io',
    
    // external reference pages
    'http.dev',
    'developer.mozilla.org',
    'spec.openapis.org',
    'medium.com',
    'www.ietf.org',
    'datatracker.ietf.org',
    'www.rfc-editor.org',
    'www.openapis.org',
    'sourcefirst.com',
    'symfony.com',
    'xxhash.com',
    'expr-lang.org',
    'opencypher.org',
    'ember-nexus.github.io',
    'opengql.github.io',
    'en.wikipedia.org',
    'www.php.net'
]

// ── helpers ──

/** Extract the hostname from a URL, returning null if the URL cannot be parsed. */
function extractHostname(url: string): string | null {
    try {
        return new URL(url).hostname
    } catch {
        return null
    }
}

/** Return true if `hostname` is allowed by the whitelist. */
function isDomainAllowed(hostname: string): boolean {
    if (DOMAIN_WHITELIST.length === 0) return false
    return DOMAIN_WHITELIST.some(
        (allowed) => hostname === allowed || hostname.endsWith(`.${allowed}`),
    )
}

/**
 * Match all absolute URLs (http:// or https://) in a string.
 * The regex stops at common prose delimiters: whitespace, quotes, angle brackets,
 * closing parentheses/brackets, and backticks so that Markdown/MDX link syntax
 * is handled correctly.
 */
const FULL_URL_RE = /https?:\/\/[^\s"'<>()\[\]`]+/g

function extractFullUrls(text: string): string[] {
    return [...text.matchAll(FULL_URL_RE)].map((m) => m[0])
}

// ── MDX files ──

const MDX_ROOT = join(import.meta.dirname, '../../../src/data')

function findMdxFiles(dir: string): string[] {
    return (readdirSync(dir, { recursive: true, withFileTypes: false }) as string[])
        .filter((f) => f.endsWith('.mdx'))
        .map((f) => join(dir, f))
}

interface MdxLinkCase {
    relPath: string
    relPathShort: string
    url: string
    urlShort: string
    hostname: string
}

const mdxLinkCases: MdxLinkCase[] = findMdxFiles(MDX_ROOT).flatMap((absPath) => {
    const relPath = absPath.replace(MDX_ROOT + '/', '')
    const text = readFileSync(absPath, 'utf-8')
    return extractFullUrls(text).flatMap((url) => {
        const hostname = extractHostname(url)
        if (!hostname) return []
        const urlShort = url.length > 60 ? url.slice(0, 60) + '…' : url
        const relPathShort = relPath.length > 50 ? '…' + relPath.slice(-50) : relPath
        return [{ relPath, relPathShort, url, urlShort, hostname }]
    })
})

describe('MDX files — external link domain whitelist', () => {
    it('at least one external link was found', () => {
        expect(mdxLinkCases.length).toBeGreaterThan(0)
    })

    it.each(mdxLinkCases)('$relPathShort — $urlShort', ({ relPath, url, hostname }) => {
        if (!isDomainAllowed(hostname)) {
            const err = new Error(`file ${relPath} contains link ${url} with not whitelisted domain ${hostname}`)
            err.stack = err.message
            throw err
        }
    })
})

// ── Swagger spec ──

const SWAGGER_PATH = join(import.meta.dirname, '../../../src/data/swagger.json')

interface SwaggerLinkCase {
    url: string
    urlShort: string
    hostname: string
}

const swaggerText = readFileSync(SWAGGER_PATH, 'utf-8')

const swaggerLinkCases: SwaggerLinkCase[] = extractFullUrls(swaggerText).flatMap((url) => {
    const hostname = extractHostname(url)
    if (!hostname) return []
    const urlShort = url.length > 60 ? url.slice(0, 60) + '…' : url
    return [{ url, urlShort, hostname }]
})

describe('swagger.json — external link domain whitelist', () => {
    it('at least one external link was found', () => {
        expect(swaggerLinkCases.length).toBeGreaterThan(0)
    })

    it.each(swaggerLinkCases)('swagger.json — $urlShort', ({ url, hostname }) => {
        if (!isDomainAllowed(hostname)) {
            const err = new Error(`file swagger.json contains link ${url} with not whitelisted domain ${hostname}`)
            err.stack = err.message
            throw err
        }
    })
})
