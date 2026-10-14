import { describe, it, expect } from 'vitest'
import { readdirSync, readFileSync } from 'fs'
import { join, sep } from 'path'

const PATHS_DIR = join(import.meta.dirname, '../../../src/data/swagger/paths')
const THREE_DIGIT_NUMBER = /\b\d{3}\b/g
const UUID_PATTERN = /[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/gi

/**
 * Recursively delete all `examples` keys from objects nested under
 * responses.<code>.content.<type>.examples, as defined by the OpenAPI path
 * structure used in this project.
 *
 * The path structure is:
 *   paths -> <path> -> <method> -> responses -> <statusCode> -> content -> <mediaType> -> examples
 */
function stripExamplesFromResponses(obj: unknown): unknown {
    if (obj === null || typeof obj !== 'object') return obj
    if (Array.isArray(obj)) return obj.map(stripExamplesFromResponses)

    const record = obj as Record<string, unknown>
    const result: Record<string, unknown> = {}

    for (const [key, value] of Object.entries(record)) {
        if (key === 'responses') {
            // value is an object keyed by status code
            const responses = value as Record<string, unknown>
            const cleanedResponses: Record<string, unknown> = {}
            for (const [code, response] of Object.entries(responses)) {
                const resp = response as Record<string, unknown>
                if (resp && typeof resp === 'object' && !Array.isArray(resp) && 'content' in resp) {
                    const content = resp['content'] as Record<string, unknown>
                    const cleanedContent: Record<string, unknown> = {}
                    for (const [mediaType, mediaObj] of Object.entries(content)) {
                        const media = mediaObj as Record<string, unknown>
                        // Drop the `examples` key entirely
                        const { examples: _dropped, ...rest } = media
                        cleanedContent[mediaType] = rest
                    }
                    cleanedResponses[code] = { ...resp, content: cleanedContent }
                } else {
                    cleanedResponses[code] = response
                }
            }
            result[key] = cleanedResponses
        } else {
            result[key] = stripExamplesFromResponses(value)
        }
    }

    return result
}

const testCases = readdirSync(PATHS_DIR, { recursive: true })
    .filter(filename => (filename as string).endsWith('.json'))
    // The `error/` group documents static `/error/<code>/<slug>` pages (e.g. get-error-500-...json).
    // The 3-digit number there identifies which problem type the page explains, not the HTTP status
    // of the (always 200) GET request itself, so it doesn't fit this filename-encodes-status-code check.
    .filter(filename => !(filename as string).startsWith(`error${sep}`))
    .flatMap(filename => {
        const statusCode = (filename as string).match(/\b(\d{3})\b/)?.[1]
        if (!statusCode) return []
        return [{ filename: filename as string, statusCode, fullPath: join(PATHS_DIR, filename as string) }]
    })

describe('Swagger path files — status code integrity', () => {

    it('at least one test file was found', () => {
        expect(testCases.length).toBeGreaterThan(0)
    })

    describe.each(testCases)('$filename (status: $statusCode)', ({ filename, statusCode, fullPath }) => {
        const raw = readFileSync(fullPath, 'utf-8')

        // 1. Parse as JSON and strip examples blocks to avoid false matches
        const parsed = JSON.parse(raw)
        const cleaned = stripExamplesFromResponses(parsed)

        // 2. Re-encode to string, then replace all UUIDs before scanning for 3-digit numbers
        const withoutUuids = JSON.stringify(cleaned).replace(UUID_PATTERN, '<uuid>')

        const foundCodes = [...new Set(withoutUuids.match(THREE_DIGIT_NUMBER) ?? [])]

        it('contains the expected status code', () => {
            expect(foundCodes, `file ${filename} does not contain status code ${statusCode}`).toContain(statusCode)
        })

        it('contains no other 3-digit codes', () => {
            const unexpected = foundCodes.filter(code => code !== statusCode)
            expect(unexpected, `unexpected codes found in file ${filename}: ${unexpected.join(', ')}`).toHaveLength(0)
        })
    })

})
