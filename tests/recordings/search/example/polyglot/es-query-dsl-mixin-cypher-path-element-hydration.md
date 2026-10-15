# Elasticsearch -> Cypher -> Hydration

In this search request we first query Elasticsearch for any plants containing the name `lily` in their descriptions.
The results are then expanded by Neo4j to also return their taxonomy relations.  
Finally the paths are hydrated and returned.

This example effectively queries a subgraph, starting with a full text search query.

## Request

<div class="code-title">Request Payload (payload.json)</div>

[Request Payload](es-query-dsl-mixin-cypher-path-element-hydration/request-payload.json ':include :type=code')

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

[Response Header](es-query-dsl-mixin-cypher-path-element-hydration/response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](es-query-dsl-mixin-cypher-path-element-hydration/response-body.json ':include :type=code')
