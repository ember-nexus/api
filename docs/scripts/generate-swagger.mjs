#!/usr/bin/env node
// Regenerates docs/src/data/swagger.json from its fragments under docs/src/data/swagger/**.
//
// Two steps, both done here so there is a single command/dependency (no jq):
//   1. Deep-merge every fragment, mirroring jq's `reduce .[1:][] as $x (.[0]; . * $x)`: objects
//      are merged key by key, anything else (including arrays) is replaced wholesale by the
//      later fragment. Every point where two fragments actually contribute keys to the same
//      object has its result's keys sorted, so the output is byte-stable regardless of
//      filesystem enumeration order or the host's locale - both of which affect plain
//      `sort`/directory listing and would otherwise make the "is swagger.json up to date" CI hash
//      check flaky across machines. A subtree that comes wholesale from a single fragment (most
//      of the tree, including every recorded example payload below) is never touched by this and
//      keeps its natural, readable key order.
//   2. Walk the merged spec for `x-ember-nexus-recording` markers and inject the *current*
//      content of the referenced file under tests/Recordings/ - this is what keeps examples
//      sourced from the real recorded HTTP traffic instead of a hand-copied duplicate. A `.json`
//      recording becomes a structured example `value`; anything else (e.g. the static
//      text/plain error pages) is used as the literal string value. A marker next to `har`
//      instead sets `har.postData.text` to the recording's minified-but-byte-faithful JSON text.
//      A recorded Problem+JSON body (one with an `instance` field) has its domain and `instance`
//      URN replaced with a fixed placeholder - see `normalizeProblemRecording` - since those two
//      values are unique per request and would otherwise make the output non-deterministic.
//
// Path resolution is relative to this file, so it works unmodified both on the host (repo
// checkout) and inside the docs dev container, as long as the container also mounts
// tests/Recordings at the same relative location (see tools/docker-compose.yml).
import { readFileSync, writeFileSync, readdirSync, statSync } from 'node:fs';
import { join, relative, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const docsRoot = join(dirname(fileURLToPath(import.meta.url)), '..');
const fragmentsDir = join(docsRoot, 'src', 'data', 'swagger');
const outputFile = join(docsRoot, 'src', 'data', 'swagger.json');
const recordingsRoot = join(docsRoot, '..', 'tests', 'Recordings');

function collectJsonFiles(dir) {
  const files = [];
  for (const entry of readdirSync(dir)) {
    const fullPath = join(dir, entry);
    if (statSync(fullPath).isDirectory()) {
      files.push(...collectJsonFiles(fullPath));
    } else if (entry.endsWith('.json')) {
      files.push(fullPath);
    }
  }
  return files;
}

const isPlainObject = (value) =>
  typeof value === 'object' && value !== null && !Array.isArray(value);

function deepMerge(base, overlay) {
  if (!isPlainObject(base) || !isPlainObject(overlay)) {
    return overlay;
  }
  const result = { ...base };
  for (const [key, value] of Object.entries(overlay)) {
    result[key] = key in result ? deepMerge(result[key], value) : value;
  }
  return result;
}

const fragmentFiles = collectJsonFiles(fragmentsDir).sort();
const merged = fragmentFiles.reduce((acc, filePath) => {
  const fragment = JSON.parse(readFileSync(filePath, 'utf8'));
  return acc === null ? fragment : deepMerge(acc, fragment);
}, null);

// Strips insignificant whitespace outside of string literals, without reparsing the document into
// JS values - JSON.parse/JSON.stringify would silently normalize number literals (e.g. "10.0"
// becomes "10", since JS has no separate float type), which is exactly the kind of subtle
// departure from the real recorded bytes this script exists to avoid.
function minifyJson(raw) {
  let result = '';
  let inString = false;
  let escaped = false;
  for (const ch of raw) {
    if (inString) {
      result += ch;
      if (escaped) {
        escaped = false;
      } else if (ch === '\\') {
        escaped = true;
      } else if (ch === '"') {
        inString = false;
      }
      continue;
    }
    if (ch === '"') {
      inString = true;
      result += ch;
    } else if (!/\s/.test(ch)) {
      result += ch;
    }
  }
  return result;
}

// Problem+JSON bodies carry two values that are deliberately *not* real recorded data even when
// sourced from a real recording: the request domain (recorded against a container-internal host
// like `http://api`) and the `instance` URN (unique per request, so literally un-reproducible).
// Both get replaced with a fixed, realistic-looking placeholder - this keeps every other property
// and value exactly as recorded (per the "don't drop or alter real content" rule) while still
// making the output deterministic across regenerations.
const PLACEHOLDER_INSTANCE_URN = 'urn:uuid:00000000-0000-0000-0000-000000000000';

function normalizeProblemRecording(parsed) {
  if (!isPlainObject(parsed) || typeof parsed.instance !== 'string') {
    return parsed;
  }
  return {
    ...parsed,
    type: typeof parsed.type === 'string'
      ? parsed.type.replace(/^https?:\/\/[^/]+/, 'https://api.example.com')
      : parsed.type,
    instance: PLACEHOLDER_INSTANCE_URN,
  };
}

// Pulls a single header's value out of a recorded `*-response-header.txt` file (one "Name:
// value" pair per line). Lets a header object source its `example` from the real response instead
// of a hand-copied, endpoint-agnostic constant - this is what keeps e.g.
// `Access-Control-Allow-Methods`/`Allow` correct per endpoint (they legitimately differ: a
// sub-resource like `/element/{id}/children` only allows `GET, HEAD, OPTIONS`, resumable uploads
// allow `DELETE, HEAD, OPTIONS, PATCH`, etc.) without duplicating that knowledge by hand.
function extractRecordedHeaderValue(raw, headerName, recordingPath) {
  const prefix = `${headerName.toLowerCase()}:`;
  for (const line of raw.split('\n')) {
    const colonIndex = line.indexOf(':');
    if (colonIndex === -1) {
      continue;
    }
    if (line.slice(0, colonIndex).trim().toLowerCase() === headerName.toLowerCase()) {
      return line.slice(colonIndex + 1).trim();
    }
  }
  throw new Error(`Header "${headerName}" not found in recording ${recordingPath}`);
}

function injectRecordings(node) {
  if (Array.isArray(node)) {
    node.forEach(injectRecordings);
    return;
  }
  if (!isPlainObject(node)) {
    return;
  }

  const recordingPath = node['x-ember-nexus-recording'];
  if (typeof recordingPath === 'string') {
    const absolutePath = join(recordingsRoot, recordingPath);
    const raw = readFileSync(absolutePath, 'utf8');
    const recordingHeaderName = node['x-ember-nexus-recording-header-name'];
    if (typeof recordingHeaderName === 'string') {
      // A header object sourcing its `example` from one line of a recorded header dump, rather
      // than the whole file becoming the value (see below).
      node.example = extractRecordedHeaderValue(raw, recordingHeaderName, recordingPath);
    } else if (node.har?.postData) {
      node.har.postData.text = minifyJson(raw);
    } else {
      // A JSON recording becomes a structured example value; anything else (e.g. the .txt
      // recordings behind the static text/plain error pages) is used as the literal string value.
      node.value = recordingPath.endsWith('.json')
        ? normalizeProblemRecording(JSON.parse(raw))
        : raw;
    }
  }

  Object.values(node).forEach(injectRecordings);
}

injectRecordings(merged);

writeFileSync(outputFile, `${JSON.stringify(merged, null, 2)}\n`);
console.log(`Wrote ${relative(process.cwd(), outputFile)} from ${fragmentFiles.length} fragments.`);
