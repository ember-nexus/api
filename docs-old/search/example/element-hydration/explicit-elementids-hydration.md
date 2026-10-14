# Explicit ElementIds Hydration

The query of an element hydration step can explicitly contain all elementIds which need to be hydrated.

## Request

<div class="code-title">Request Payload (payload.json)</div>

[Request Payload](explicit-elementids-hydration/request-payload.json ':include :type=code')

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

[Response Header](explicit-elementids-hydration/response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](explicit-elementids-hydration/response-body.json ':include :type=code')
