# Implicit Elements Hydration

When the element hydration search step has no query defined, it tries to find the `elements`-property of the previous
search step result, and to then serialize its content.

## Request

<div class="code-title">Request Payload (payload.json)</div>

[Request Payload](implicit-elements-hydration/request-payload.json ':include :type=code')

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

[Response Header](implicit-elements-hydration/response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](implicit-elements-hydration/response-body.json ':include :type=code')
