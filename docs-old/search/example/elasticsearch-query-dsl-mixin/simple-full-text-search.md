# Simple Full Text Search

This example of a simple full text search matches all elements whose property `content` fuzzy matches the string
`blue`.  
For fuzzy matching multiple properties at the same time see [advanced full text search](./api-endpoints/search/example/advanced-full-text-search).

See also Elasticsearch's official documentation: [Match query](https://www.elastic.co/docs/reference/query-languages/query-dsl/query-dsl-match-query)

## Request

<div class="code-title">Request Payload (payload.json)</div>

[Request Payload](simple-full-text-search/request-payload.json ':include :type=code')

<div class="code-title">Request Command</div>

```bash
curl \
  -X POST \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer secret-token:PIPeJGUt7c00ENn8a5uDlc" \
  -d @payload.json \
  https://api.localhost/search
```

## Response

<div class="code-title auto-refresh">Response Headers</div>

[Response Header](simple-full-text-search/response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](simple-full-text-search/response-body.json ':include :type=code')
