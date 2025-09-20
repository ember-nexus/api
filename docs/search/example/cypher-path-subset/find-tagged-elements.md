# Find Tagged Elements

This example tries to find all elements which have access to specific predefined tags.

## Request

<div class="code-title">Request Payload (payload.json)</div>

[Request Payload](find-tagged-elements/request-payload.json ':include :type=code')

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

[Response Header](find-tagged-elements/response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](find-tagged-elements/response-body.json ':include :type=code')
