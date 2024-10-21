# Text Prefix Search

In a text prefix search Elasticsearch tries to find results where the provided value matches the start of possible
search results.

See also:

- [Prefix query documentation](https://www.elastic.co/docs/reference/query-languages/query-dsl/query-dsl-prefix-query)

## Request

<div class="code-title">Request Payload (payload.json)</div>

[Request Payload](text-prefix-search/request-payload.json ':include :type=code')

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

[Response Header](text-prefix-search/response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](text-prefix-search/response-body.json ':include :type=code')
