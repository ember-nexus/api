# Cypher Path Subset Search Step

The Cypher Path Subset search step is a single task, executable within [search requests](../../api-endpoints/search/post-search).

It executes a user defined Cypher query against the internal Neo4j database.

## Security aspects

The following safeguards are in place:

- Queries are executed in a **readonly transaction**, which limits access to the database dramatically.
- The **result of queries is validated and filtered** by non-user-defined queries, therefore making sure that no
  inaccessible elements are accessed.
- The **result of the search step is limited** to a list of paths, containing the filtered and validated `elementIds`.
  This makes sure that no manipulated or specifically crafted properties etc. are returned.
- The **user's query is validated against a stricter subset of Cypher 25, called [Cypher Path Subset](../../concepts/grammars?id=cypher-path-subset)**.
  It is limited to path searches only, i.e. clauses resulting in write access or side effects like existence clauses and
  function calls are forbidden.  
  The query can only return the variable `paths`, which must be of type [Path](https://neo4j.com/docs/cypher-manual/current/patterns/reference/#path-patterns).

With all the different safeguards in place and full test coverage across all guards, **this search step is considered
safe**.

## Input parameters

Any parameters available are passed as query variables to the database.

## Input query

The query must be of type string.

Expressions are not supported. Parameters are available as query variables.

## Step result

List of paths result objects, which contain a list of node ids and relation ids.

- `paths`: List of paths returned by Neo4j. Each object contains the following properties:
  - `nodeIds`: List of element ids (UUID).
  - `relationIds`: List of element ids (UUID).

## Example

### Request

<div class="code-title">Request Payload (payload.json)</div>

[Request Payload](../example/cypher-path-subset/find-tagged-elements/request-payload.json ':include :type=code')

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

[Response Header](../example/cypher-path-subset/find-tagged-elements/response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](../example/cypher-path-subset/find-tagged-elements/response-body.json ':include :type=code')

## Further examples

- [Find Tagged Elements](/search/example/cypher-path-subset/find-tagged-elements)
- [Find Nested Elements](/search/example/cypher-path-subset/find-nested-elements)
- [Parameterized Cypher Search](/search/example/cypher-path-subset/parameterized-cypher-search)
