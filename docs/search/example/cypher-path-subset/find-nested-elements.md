# Find Nested Elements

This example tries to find all elements which are nested to another element.

## Request

<div class="code-title">Request Payload (payload.json)</div>

[Request Payload](find-nested-elements/request-payload.json ':include :type=code')

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

[Response Header](find-nested-elements/response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](find-nested-elements/response-body.json ':include :type=code')
