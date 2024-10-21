# Keyword Search

In a keyword search Elasticsearch looks for an exact match. Also note that in default configurations the keyword fields
are named `original_name.keyword`. A keyword-search against a non-keyword field will result in zero results.

See also:

- [Term query documentation](https://www.elastic.co/docs/reference/query-languages/query-dsl/query-dsl-term-query)
- [Keyword type family documentation](https://www.elastic.co/docs/reference/elasticsearch/mapping-reference/keyword)

## Request

<div class="code-title">Request Payload (payload.json)</div>

[Request Payload](keyword-search/request-payload.json ':include :type=code')

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

[Response Header](keyword-search/response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](keyword-search/response-body.json ':include :type=code')
