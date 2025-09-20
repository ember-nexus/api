# Parameterized ElementIds Hydration

The query of an element hydration step can contain an expression for its `elementIds` property. The expression has
access to the search step's parameters during its evaluation.

You can read the [Expr library's documentation to explore their features](https://expr-lang.org/docs/language-definition)
and use their [playground to test your own queries](https://expr-lang.org/playground).

## Request

<div class="code-title">Request Payload (payload.json)</div>

[Request Payload](parameterized-elementids-hydration/request-payload.json ':include :type=code')

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

[Response Header](parameterized-elementids-hydration/response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](parameterized-elementids-hydration/response-body.json ':include :type=code')
