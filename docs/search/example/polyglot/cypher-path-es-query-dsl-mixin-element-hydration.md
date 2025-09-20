# Cypher -> Elasticsearch -> Hydration

In this search request we first look for `Plant` nodes which are tagged with a specific color. Results are then given to
Elasticsearch, which sorts the plants by how frequent the requested color appears in the plant's description text.  
Finally results are hydrated and returned.

The color is given as a parameter, and can be easily replaced with `blue`, `yellow` and more color strings.

Note that the Elasticsearch search step only operates on the first node of each path, which are the plants. All other
nodes, which in our case are only tags, are ignored.

## Request

<div class="code-title">Request Payload (payload.json)</div>

[Request Payload](cypher-path-es-query-dsl-mixin-element-hydration/request-payload.json ':include :type=code')

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

[Response Header](cypher-path-es-query-dsl-mixin-element-hydration/response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](cypher-path-es-query-dsl-mixin-element-hydration/response-body.json ':include :type=code')
