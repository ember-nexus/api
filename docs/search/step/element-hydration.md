# Element Hydration Search Step

The element hydration search step is a single task, executable within [search requests](../../api-endpoints/search/post-search).

The hydration step converts a list of element ids to a collection response, containing elements and all their
properties. This step is mostly used to convert results of other search steps to collection responses usable by clients.

## Security aspects

As all element ids are internally checked for access by the current user and only accessible elements are hydrated and
returned, this search step **is considered safe** and can therefore be executed by any user.

## Input parameters

This search step does not have explicit parameters defined. Parameters are available within expressions.

## Input query

The query must be an object containing the following properties:

- `elementIds`: List of element ids (string) or a single string containing an expression, which returns a list of
  element ids (string).

## Step result

List of hydrated elements.

## Example

### Request

<div class="code-title">Request Payload (payload.json)</div>

[Request Payload](../example/element-hydration/explicit-elementids-hydration/request-payload.json ':include :type=code')

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

[Response Header](../example/element-hydration/explicit-elementids-hydration/response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](../example/element-hydration/explicit-elementids-hydration/response-body.json ':include :type=code')

## Further examples

- [Implicit Elements Hydration](/search/example/element-hydration/implicit-elements-hydration)
- [Implicit Paths Hydration](/search/example/element-hydration/implicit-paths-hydration)
- [Explicit ElementIds Hydration](/search/example/element-hydration/explicit-elementids-hydration)
- [Parameterized ElementIds Hydration](/search/example/element-hydration/parameterized-elementids-hydration)
