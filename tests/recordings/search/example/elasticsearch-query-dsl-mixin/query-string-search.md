# Query String Search

Elasticsearch's query string query parses a string query against a strict syntax. This syntax is similar to the one most
search engines use, e.g. `+term` requires a term to be present, `-term` requires a term to be absent, `term~` enables
fuzzy rules on the term `term`, logical operators like `AND` and `OR`, access to properties like `propertyName:"Value"`,
wildcards, regular expressions [and more are supported](https://www.elastic.co/docs/reference/query-languages/query-dsl/query-dsl-query-string-query).

**Warning**: While this type of query enables powerful capabilities for end users, it also expects the queries to be
correct - otherwise Elasticsearch will throw an exception, which will result in a 4xx HTTP response by Ember Nexus.

See also:

- [Query string query documentation](https://www.elastic.co/docs/reference/query-languages/query-dsl/query-dsl-query-string-query)

## Request

The following request retrieves all elements containing the word `blue`, which do not mention the word `red`:

<div class="code-title">Request Payload (payload.json)</div>

[Request Payload](query-string-search/request-payload.json ':include :type=code')

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

[Response Header](query-string-search/response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](query-string-search/response-body.json ':include :type=code')
