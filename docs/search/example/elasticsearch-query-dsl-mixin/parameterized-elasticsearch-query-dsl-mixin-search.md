# Parameterized Elasticsearch Query DSL Mixin Search

Elasticsearch on its own does not support parameters in their Query DSL language. However, Ember Nexus uses the [Expr](https://github.com/expr-lang/expr)
library to integrate expressions directly into the Query DSL query.  
These expressions need to be defined as strings surrounded by two curly brackets, e.g. `{{your_expression}}`, and their
result will replace the expression string inside the query. The replaced value can be a string itself, but also all
other JSON data types like numbers, arrays and objects are supported.  
Expressions will receive the search step's parameters as their only input.

You can read the [Expr library's documentation to explore their features](https://expr-lang.org/docs/language-definition)
and use their [playground to test your own queries](https://expr-lang.org/playground).

## Request

The following example defines a list of element ids in a parameter called `someElementIds`, which are then retrieved via
Elasticsearch, and its results are then hydrated.  
While this specific example does not offer advantages over an individual hydration search step, you can combine it with
all other Elasticsearch Query DSL features.

Note that the Query DSL string `"{{someElementIds}}"` is expanded to `["258c0dfe-b1d8-4839-beed-d00d1b544a96", "23508e6c-632a-496d-9bd6-8c62a03216af", "8940d70b-5b6f-43b7-bee4-41d073396ff8"]`
before it is executed by Elasticsearch. You can see this in the response's debug data.

<div class="code-title">Request Payload (payload.json)</div>

[Request Payload](parameterized-elasticsearch-query-dsl-mixin-search/request-payload.json ':include :type=code')

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

[Response Header](parameterized-elasticsearch-query-dsl-mixin-search/response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](parameterized-elasticsearch-query-dsl-mixin-search/response-body.json ':include :type=code')
