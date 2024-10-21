# Advanced Full Text Search

This example of an advanced full text search. It works in the following steps:

- Find only elements accessible by the current user; implicitly defined through the `elasticsearch-query-dsl-mixin`
  search step.
- Each search result must satisfy at least one these requirements (`bool`-query with `minimum_should_match = 1`):
  - Property `name` matches against the term `blue` in a fuzzy manner (different spellings etc. are accepted). The boost
    score of `10.0` marks matches as more important, moving them to the front of the search results.
  - Property `description` matches against the term `blue`.
- All search results are hydrated.

See also:

- [Match query documentation](https://www.elastic.co/docs/reference/query-languages/query-dsl/query-dsl-match-query)
- [Explanation of fuzziness](https://www.elastic.co/docs/reference/query-languages/query-dsl/query-dsl-match-query#query-dsl-match-query-fuzziness)
- [Boolean query documentation](https://www.elastic.co/docs/reference/query-languages/query-dsl/query-dsl-bool-query)
- [Multi match documentation](https://www.elastic.co/docs/reference/query-languages/query-dsl/query-dsl-multi-match-query), can be used as an alternative to one boolean query and multiple match queries

## Request

<div class="code-title">Request Payload (payload.json)</div>

[Request Payload](advanced-full-text-search/request-payload.json ':include :type=code')

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

[Response Header](advanced-full-text-search/response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](advanced-full-text-search/response-body.json ':include :type=code')
