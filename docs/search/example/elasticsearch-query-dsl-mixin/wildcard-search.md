# Wildcard Search

In a wildcard search Elasticsearch tries to find results where sections of the input query contain gaps. These gaps can
either be a single character (`?`) or be of undefined length, including zero characters (`*`).

See also:

- [Wildcard query documentation](https://www.elastic.co/docs/reference/query-languages/query-dsl/query-dsl-wildcard-query)

## Request

The following example will match both the word `color` as well as `colour`.

<div class="code-title">Request Payload (payload.json)</div>

[Request Payload](wildcard-search/request-payload.json ':include :type=code')

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

[Response Header](wildcard-search/response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](wildcard-search/response-body.json ':include :type=code')
