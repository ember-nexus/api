import http from 'k6/http';
import { check, sleep } from 'k6';
import exec from 'k6/execution';
import { Trend } from 'k6/metrics';

const BASE_URL = __ENV.API_BASE_URL || 'http://api';
const API_TOKEN = __ENV.API_TOKEN || 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';
const N = __ENV.N ? parseInt(__ENV.N, 10) : 1;
const K = __ENV.K ? parseInt(__ENV.K, 10) : 1;

const MIN_FILE_SIZE = 3 * 1024;
const MAX_FILE_SIZE = 30 * 1024 * 1024;

const MIN_ITERATION_DELAY = 0.1; // seconds
const MAX_ITERATION_DELAY = 3; // seconds

// Upload size categories used to break down request timings. Roughly one bucket per decade
// between MIN_FILE_SIZE and MAX_FILE_SIZE, matching the log-uniform size distribution below so
// each bucket gets approximately the same number of samples.
const SIZE_BUCKETS = [
  { key: 'lt_10kb', label: '< 10 KB', maxBytes: 10 * 1024 },
  { key: 'lt_100kb', label: '< 100 KB', maxBytes: 100 * 1024 },
  { key: 'lt_1mb', label: '< 1 MB', maxBytes: 1024 * 1024 },
  { key: 'lt_10mb', label: '< 10 MB', maxBytes: 10 * 1024 * 1024 },
  { key: 'gte_10mb', label: '>= 10 MB', maxBytes: Infinity },
];

function sizeBucketFor(sizeInBytes) {
  return SIZE_BUCKETS.find((bucket) => sizeInBytes < bucket.maxBytes);
}

const nodeCreationTrends = {};
const fileUploadTrends = {};
for (const bucket of SIZE_BUCKETS) {
  nodeCreationTrends[bucket.key] = new Trend(`node_creation_duration_${bucket.key}`, true);
  fileUploadTrends[bucket.key] = new Trend(`file_upload_duration_${bucket.key}`, true);
}

export const options = {
  scenarios: {
    default: {
      executor: 'shared-iterations',
      vus: K,
      iterations: N,
      maxDuration: '30m',
    },
  },
  summaryTrendStats: ['avg', 'min', 'med', 'max', 'p(90)', 'p(95)', 'count'],
};

function authHeaders(extra) {
  return Object.assign({ Authorization: `Bearer ${API_TOKEN}` }, extra);
}

// Extracts the UUID of a newly created element from a POST response's Location header,
// e.g. "/455a89b9-4ec7-4b02-b023-bcfbcbba9a9f" -> "455a89b9-4ec7-4b02-b023-bcfbcbba9a9f"
function extractIdFromLocation(res) {
  const location = res.headers['Location'];
  check(res, { 'response has Location header': () => undefined !== location });
  return location.replace(/^\//, '');
}

function formattedTimestamp() {
  const now = new Date();
  const pad = (value) => String(value).padStart(2, '0');
  return `${now.getFullYear()}.${pad(now.getMonth() + 1)}.${pad(now.getDate())} ${pad(now.getHours())}:${pad(now.getMinutes())}`;
}

// Log-uniform size in [min, max] - roughly the same number of samples per order of magnitude
// (e.g. about as many results in 10 KB..100 KB as in 1 MB..10 MB), rather than per linear unit.
function randomFileSize(min, max) {
  const logMin = Math.log(min);
  const logMax = Math.log(max);
  return Math.floor(Math.exp(logMin + Math.random() * (logMax - logMin)));
}

// A pure per-byte Math.random() loop is far too slow for payloads up to 30 MB. Instead, a small
// block of random bytes is generated once and tiled to the requested size - the content doesn't
// need to be stored or verified, it just needs to be an arbitrary byte stream.
const RANDOM_BLOCK_SIZE = 64 * 1024;
const randomBlock = new Uint8Array(RANDOM_BLOCK_SIZE);
for (let i = 0; i < RANDOM_BLOCK_SIZE; i++) {
  randomBlock[i] = Math.floor(256 * Math.random());
}

function randomBytes(size) {
  const bytes = new Uint8Array(size);
  for (let offset = 0; offset < size; offset += RANDOM_BLOCK_SIZE) {
    const chunkSize = Math.min(RANDOM_BLOCK_SIZE, size - offset);
    bytes.set(randomBlock.subarray(0, chunkSize), offset);
  }
  return bytes;
}

function createParentNode(bucket) {
  const res = http.post(
    `${BASE_URL}/`,
    JSON.stringify({
      type: 'Collection',
      data: {
        name: `k6 ${formattedTimestamp()}`,
      },
    }),
    { headers: authHeaders({ 'Content-Type': 'application/json' }), tags: { operation: 'node_creation', bucket: bucket.key } }
  );

  check(res, { 'create parent node: status is 201': (r) => 201 === r.status });
  nodeCreationTrends[bucket.key].add(res.timings.duration);

  return extractIdFromLocation(res);
}

function createFileNode(parentId, n, bucket) {
  const res = http.post(
    `${BASE_URL}/${parentId}`,
    JSON.stringify({
      type: 'File',
      data: {
        name: `file ${n}`,
      },
    }),
    { headers: authHeaders({ 'Content-Type': 'application/json' }), tags: { operation: 'node_creation', bucket: bucket.key } }
  );

  check(res, { 'create file node: status is 201': (r) => 201 === r.status });
  nodeCreationTrends[bucket.key].add(res.timings.duration);

  return extractIdFromLocation(res);
}

function uploadRandomFile(nodeId, n, size, bucket) {
  const data = randomBytes(size);

  const res = http.post(`${BASE_URL}/${nodeId}/file`, data.buffer, {
    headers: authHeaders({ 'Content-Disposition': `inline; filename=${n}.bin` }),
    tags: { operation: 'file_upload', bucket: bucket.key },
  });

  check(res, { 'upload file: status is 201': (r) => 201 === r.status });
  fileUploadTrends[bucket.key].add(res.timings.duration);
}

export default function () {
  const n = exec.scenario.iterationInTest + 1;
  const size = randomFileSize(MIN_FILE_SIZE, MAX_FILE_SIZE);
  const bucket = sizeBucketFor(size);

  const parentId = createParentNode(bucket);
  const fileNodeId = createFileNode(parentId, n, bucket);
  uploadRandomFile(fileNodeId, n, size, bucket);

  sleep(MIN_ITERATION_DELAY + Math.random() * (MAX_ITERATION_DELAY - MIN_ITERATION_DELAY));
}

function formatMs(value) {
  return undefined === value ? '-' : `${value.toFixed(1)} ms`;
}

function trendRow(label, trendMetric) {
  const values = trendMetric ? trendMetric.values : undefined;
  if (!values) {
    return `<tr><td>${label}</td><td colspan="6">no data</td></tr>`;
  }
  return `<tr>
    <td>${label}</td>
    <td>${values.count}</td>
    <td>${formatMs(values.avg)}</td>
    <td>${formatMs(values.min)}</td>
    <td>${formatMs(values.med)}</td>
    <td>${formatMs(values['p(90)'])}</td>
    <td>${formatMs(values['p(95)'])}</td>
    <td>${formatMs(values.max)}</td>
  </tr>`;
}

function buildHtmlReport(data) {
  const nodeCreationRows = SIZE_BUCKETS.map((bucket) =>
    trendRow(bucket.label, data.metrics[`node_creation_duration_${bucket.key}`])
  ).join('\n');
  const fileUploadRows = SIZE_BUCKETS.map((bucket) =>
    trendRow(bucket.label, data.metrics[`file_upload_duration_${bucket.key}`])
  ).join('\n');

  const checksSummary = checksSummaryText(data);

  return `<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>k6 demo report</title>
<style>
  body { font-family: system-ui, sans-serif; margin: 2rem; color: #1a1a1a; }
  h1 { margin-bottom: 0.25rem; }
  .meta { color: #555; margin-bottom: 1.5rem; }
  table { border-collapse: collapse; margin-bottom: 2rem; width: 100%; max-width: 820px; }
  th, td { border: 1px solid #ddd; padding: 0.5rem 0.75rem; text-align: right; }
  th:first-child, td:first-child { text-align: left; }
  th { background: #f4f4f4; }
  tr:nth-child(even) { background: #fafafa; }
</style>
</head>
<body>
  <h1>k6 demo report</h1>
  <p class="meta">
    Generated ${new Date().toISOString()} &middot; N=${N} iterations &middot; K=${K} VUs &middot;
    checks: ${checksSummary}
  </p>

  <h2>Node creation duration by upload size bucket</h2>
  <table>
    <thead>
      <tr><th>Bucket</th><th>Count</th><th>Avg</th><th>Min</th><th>Median</th><th>p90</th><th>p95</th><th>Max</th></tr>
    </thead>
    <tbody>
      ${nodeCreationRows}
    </tbody>
  </table>

  <h2>File upload duration by size bucket</h2>
  <table>
    <thead>
      <tr><th>Bucket</th><th>Count</th><th>Avg</th><th>Min</th><th>Median</th><th>p90</th><th>p95</th><th>Max</th></tr>
    </thead>
    <tbody>
      ${fileUploadRows}
    </tbody>
  </table>
</body>
</html>`;
}

function textTrendRow(label, trendMetric) {
  const values = trendMetric ? trendMetric.values : undefined;
  if (!values) {
    return `  ${label.padEnd(10)} no data`;
  }
  return `  ${label.padEnd(10)} count=${String(values.count).padEnd(6)} avg=${formatMs(values.avg).padEnd(10)} med=${formatMs(values.med).padEnd(10)} p90=${formatMs(values['p(90)']).padEnd(10)} p95=${formatMs(values['p(95)']).padEnd(10)} max=${formatMs(values.max)}`;
}

function checksSummaryText(data) {
  const checks = data.metrics.checks;
  if (!checks) {
    return '-';
  }
  const passes = checks.values.passes;
  const total = passes + checks.values.fails;
  return `${(checks.values.rate * 100).toFixed(2)}% succeeded (${passes}/${total})`;
}

function buildTextSummary(data) {
  const lines = [
    '',
    `k6 demo summary — N=${N} iterations, K=${K} VUs`,
    `checks: ${checksSummaryText(data)}`,
    '',
    'Node creation duration by upload size bucket:',
    ...SIZE_BUCKETS.map((bucket) => textTrendRow(bucket.label, data.metrics[`node_creation_duration_${bucket.key}`])),
    '',
    'File upload duration by size bucket:',
    ...SIZE_BUCKETS.map((bucket) => textTrendRow(bucket.label, data.metrics[`file_upload_duration_${bucket.key}`])),
    '',
    'Full HTML report: tmp/report.html',
    '',
  ];

  return lines.join('\n');
}

export function handleSummary(data) {
  return {
    'tmp/report.html': buildHtmlReport(data),
    stdout: buildTextSummary(data),
  };
}
