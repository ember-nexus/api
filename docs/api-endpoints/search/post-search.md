# <span class="title-url"><span class="method-post">POST</span>` /search`</span><span class="title-human">Search Endpoint</span>

<!-- panels:start -->
<!-- div:left-panel -->

The search endpoint allows you to query internal databases like Neo4j and Elasticsearch with user defined queries,
returning exactly the data you need in the most optimal way.  
Each search request can contain multiple search steps, which are executed sequentially. Later steps have access to data
returned by earlier steps.

**WHY IT MATTERS**: Chaining steps together enables to search across different databases, uncovering insights that
single-database queries cannot.

## Supported Search Steps

- [Elasticsearch Query DSL Mixin](/search/step/elasticsearch-query-dsl-mixin): Ideal for finding elements
  based solely on their own properties. Supports all [Elasticsearch Query DSL features](https://www.elastic.co/docs/explore-analyze/query-filter/languages/querydsl).
- [Cypher Path](/search/step/cypher-path-subset): Ideal to find collections of elements based on the relation
  between them. Uses a safe subset of [Cypher 25](https://neo4j.com/docs/cypher-manual/25/queries/concepts/).
- [Element Hydration](/search/step/element-hydration): Converts a list of element ids to a list containing
  the full element objects, i.e. all its properties "hydrated". Useful as a last step in a search query to return
  collection responses.

## Request Parameters

This endpoint does not require parameters.

## Request Headers

<div class="table-request-headers">

| Header          | Description                                                                                         | Required | Default |
|-----------------|-----------------------------------------------------------------------------------------------------|----------|---------|
| `Authorization` | Contains an authentication token. <br />See [authentication](/concepts/authentication) for details. | no       | -       |

</div>

## Response Headers

This endpoint does not expose specialized response headers.

## Request Body

The posted request must be a valid JSON document.

The request can contain the following attributes:

- `debug`: Optional, boolean. If set to true, the response object will contain additional details like intermediate
  search results, performance data and more.
- `parameters`: Optional, object of arbitrary form. All top level or "global" parameters are injected to search steps to
  provide static data to queries.
- `steps`: Required, list of step definitions.

Each step definition can have the following attributes:

- `type`: Required, string. Must match one of the supported search steps, e.g. `element-hydration`.
- `query`: Optional, if set it must be either a string or object. Content depends on the used search step.
- `parameters`: Optional, object of arbitrary form. Has priority over global parameters, i.e. overwrites them for the
  current step.

The parameters of a step are evaluated in the following order:

- Results of previous steps are used as the base.
- Later step results will overwrite earlier step results.
- Global parameters will overwrite step results.
- Step specific parameters will overwrite global parameters as well as step results.

## Examples

Elasticsearch Query DSL Mixins

- [Simple Full Text Search](/search/example/elasticsearch-query-dsl-mixin/simple-full-text-search)
- [Advanced Full Text Search](/search/example/elasticsearch-query-dsl-mixin/advanced-full-text-search)
- [Keyword Search](/search/example/elasticsearch-query-dsl-mixin/keyword-search)
- [Text Prefix Search](/search/example/elasticsearch-query-dsl-mixin/text-prefix-search)
- [Wildcard Search](/search/example/elasticsearch-query-dsl-mixin/wildcard-search)
- [Query String Search](/search/example/elasticsearch-query-dsl-mixin/query-string-search)
- [Parameterized Search](/search/example/elasticsearch-query-dsl-mixin/parameterized-elasticsearch-query-dsl-mixin-search)

Cypher Path Subset

- [Find Tagged Elements](/search/example/cypher-path-subset/find-tagged-elements)
- [Find Nested Elements](/search/example/cypher-path-subset/find-nested-elements)
- [Parameterized Cypher Search](/search/example/cypher-path-subset/parameterized-cypher-search)

Element Hydration

- [Implicit Elements Hydration](/search/example/element-hydration/implicit-elements-hydration)
- [Implicit Paths Hydration](/search/example/element-hydration/implicit-paths-hydration)
- [Explicit ElementIds Hydration](/search/example/element-hydration/explicit-elementids-hydration)
- [Parameterized ElementIds Hydration](/search/example/element-hydration/parameterized-elementids-hydration)

Polyglot

- [Elasticsearch -> Cypher -> Hydration](/search/example/polyglot/es-query-dsl-mixin-cypher-path-element-hydration)
- [Cypher -> Elasticsearch -> Hydration](/search/example/polyglot/cypher-path-es-query-dsl-mixin-element-hydration)
