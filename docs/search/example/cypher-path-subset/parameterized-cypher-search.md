# Parameterized Cypher Search

This example tries to find all elements which are tagged with elements, whose identifiers are defined in parameters.

## Request

<div class="code-title">Request Payload (payload.json)</div>

[Request Payload](parameterized-cypher-search/request-payload.json ':include :type=code')

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

[Response Header](parameterized-cypher-search/response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](parameterized-cypher-search/response-body.json ':include :type=code')
