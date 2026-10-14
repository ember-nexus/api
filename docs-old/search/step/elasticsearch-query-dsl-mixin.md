# Elasticsearch Query DSL Mixin Search Step

The Elasticsearch Query DSL Mixin Search Step combines a user defined Elasticsearch Query DSL with an internal Query DSL
mixin, which guarantees that the user defined query can only work on elements the current user has access to.

Otherwise, all query features of the Elasticsearch Query DSL language are supported.

## Security aspects

This search step is limited in three ways:

- Elasticsearch search requests are readonly by default.
- Only elements accessible by the user can be searched.
- Only accessible element ids are returned.  
  While this removes support for aggregated properties and direct property lookups, this approach also disables
  additional dangerous approaches like the [terms lookup query](https://www.elastic.co/docs/reference/query-languages/query-dsl/query-dsl-terms-query#query-dsl-terms-lookup)
  and [joins](https://www.elastic.co/docs/reference/query-languages/query-dsl/joining-queries). Note that joins are
  considered to be expensive, and are therefore already disabled in normal Elasticsearch deployments.

As there is no way to manipulate or exfiltrate arbitrary data, this search step **is considered safe** and can therefore
be executed by any user.

## Input parameters

- `nodeTypes`: Optional, list of nodes types. If provided the search query is restricted to the provided node types.
- `relationTypes`: Optional, list of relation types. If provided the search query is restricted to the provided relation
  types.
- `page`: Optional, int, default is 1. It is the number of result page to return content from.
- `pageSize`: Optional, int, default is 25. It limits the number of results per page.
- `minScore`: Optional, numeric, default is `null`. If it is set to a numeric value like `1.0`, then it removes all
  search results with a lower score.

## Input query

The query must be a JSON object following the Elasticsearch Query DSL.

## Step result

- `elements`: List of search element objects returned by Elasticsearch. Each search element object contains the
  following properties:
  - `id`: Element id (UUID).
  - `type`: Type of the element.
  - `metadata`: Object with the following properties:
    - `score`: Numeric value returned by Elasticsearch. A high score implies a high fit between the query and the
      element.
- `totalHits`: Integer, number of total elements found by the query. Can be used to calculate how many pages of
  results are available. Very high numbers of results might be rounded or estimates by Elasticsearch.
- `maxScore`: Maximum score achieved of any element, regardless if it is part of the current page results or not.

## Example

### Request

<div class="code-title">Request Payload (payload.json)</div>

[Request Payload](../example/elasticsearch-query-dsl-mixin/simple-full-text-search/request-payload.json ':include :type=code')

<div class="code-title">Request Command</div>

```bash
curl \
  -X POST \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer secret-token:PIPeJGUt7c00ENn8a5uDlc" \
  -d @payload.json \
  https://api.localhost/search
```

### Response

<div class="code-title auto-refresh">Response Headers</div>

[Response Header](../example/elasticsearch-query-dsl-mixin/simple-full-text-search/response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](../example/elasticsearch-query-dsl-mixin/simple-full-text-search/response-body.json ':include :type=code')

## Further examples

- [Simple Full Text Search](/search/example/elasticsearch-query-dsl-mixin/simple-full-text-search)
- [Advanced Full Text Search](/search/example/elasticsearch-query-dsl-mixin/advanced-full-text-search)
- [Keyword Search](/search/example/elasticsearch-query-dsl-mixin/keyword-search)
- [Text Prefix Search](/search/example/elasticsearch-query-dsl-mixin/text-prefix-search)
- [Wildcard Search](/search/example/elasticsearch-query-dsl-mixin/wildcard-search)
- [Query String Search](/search/example/elasticsearch-query-dsl-mixin/query-string-search)
- [Parameterized Search](/search/example/elasticsearch-query-dsl-mixin/parameterized-elasticsearch-query-dsl-mixin-search)
