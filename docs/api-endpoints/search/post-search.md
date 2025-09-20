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
- [Cypher Path](/search/step/cypher-path): Ideal to find collections of elements based on the relation
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

Examples using mostly Elasticsearch Query DSL Mixins:

- [Simple Full Text Search](/search/example/simple-full-text-search)
- [Advanced Full Text Search](/search/example/advanced-full-text-search)
- [Keyword Search](/search/example/keyword-search)
- [Text Prefix Search](/search/example/text-prefix-search)
- [Wildcard Search](/search/example/wildcard-search)
- [Query String Search](/search/example/query-string-search)

Examples using mostly Cypher Paths:

- [Find Tagged Elements](./)
- [Find Elements by Type (Cypher)](./)
- [Find Nested Elements](./)

## Request Example

```bash
curl \
  -X POST \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer secret-token:XtNzGLwxpDFtrjv68nvoUG" \
  -d '{"query": {"term": {"test.keyword": "7-01"}}}' \
  https://api.localhost/search
```

<!-- tabs:start -->

### **Success 200**

```json
{
  "type": "_PartialElementCollection",
  "id": "/",
  "totalElements": 10,
  "links": {
    "first": "/",
    "previous": null,
    "next": null,
    "last": "/"
  },
  "elements": [
    {
      "type": "Token",
      "id": "f34e181f-b5dd-4e3b-8961-917e64a54422",
      "data": {
        "created": "2023-05-28 10:27:10",
        "hash": "363ab392272e2337fef5d1916b1151f0178ed320b6416b80e039801cd1fc271e",
        "note": "Token contains token only due to testing purposes.",
        "test": "7-01",
        "token": "secret-token:Urhq1nO0p8jQnAZER721r5",
        "updated": "2023-05-28 10:27:10"
      }
    },
    {
      "type": "User",
      "id": "14974a41-6e2d-453d-9f89-c64d99cef062",
      "data": {
        "created": "2023-05-28 10:27:10",
        "email": "user5@7-01.localhost.de",
        "name": "User 5",
        "test": "7-01",
        "updated": "2023-05-28 10:27:10"
      }
    },
    {
      "type": "Data",
      "id": "2cacaa15-8920-43d4-b885-f53a90035aef",
      "data": {
        "created": "2023-05-28 10:27:10",
        "name": "Data 3",
        "test": "7-01",
        "updated": "2023-05-28 10:27:10"
      }
    },
    {
      "type": "Data",
      "id": "3a3c2f8b-d1bd-40fd-b381-82de60539c9f",
      "data": {
        "created": "2023-05-28 10:27:10",
        "name": "Data 1",
        "test": "7-01",
        "updated": "2023-05-28 10:27:10"
      }
    },
    {
      "type": "Data",
      "id": "c1bacef0-bdfc-4b13-b2fa-062bea9c0372",
      "data": {
        "created": "2023-05-28 10:27:10",
        "name": "Data 2",
        "test": "7-01",
        "updated": "2023-05-28 10:27:10"
      }
    },
    {
      "type": "OWNS",
      "id": "6ffb4bb1-7d9d-4002-b529-fa18bb18e854",
      "start": "3a3c2f8b-d1bd-40fd-b381-82de60539c9f",
      "end": "c1bacef0-bdfc-4b13-b2fa-062bea9c0372",
      "data": {
        "created": "2023-05-28 10:27:12",
        "test": "7-01",
        "updated": "2023-05-28 10:27:12"
      }
    },
    {
      "type": "Group",
      "id": "8967e985-5ef4-4dfb-b3bc-32a997a39a3d",
      "data": {
        "created": "2023-05-28 10:27:10",
        "name": "Group 1",
        "test": "7-01",
        "updated": "2023-05-28 10:27:10"
      }
    },
    {
      "type": "Group",
      "id": "e8e1e3fb-31f2-4479-b899-bedb8c01feb6",
      "data": {
        "created": "2023-05-28 10:27:10",
        "name": "Group 2",
        "test": "7-01",
        "updated": "2023-05-28 10:27:10"
      }
    },
    {
      "type": "HAS_READ_ACCESS",
      "id": "67674e12-d21d-4fdc-b61a-3305610a9949",
      "start": "2cacaa15-8920-43d4-b885-f53a90035aef",
      "end": "ef8a72b3-87d0-478c-a665-e134be8b8f7a",
      "data": {
        "created": "2023-05-28 10:27:12",
        "test": "7-01",
        "updated": "2023-05-28 10:27:12"
      }
    },
    {
      "type": "HAS_READ_ACCESS",
      "id": "917b9656-43ed-4473-a91e-e0d201cc47aa",
      "start": "c1bacef0-bdfc-4b13-b2fa-062bea9c0372",
      "end": "2cacaa15-8920-43d4-b885-f53a90035aef",
      "data": {
        "created": "2023-05-28 10:27:12",
        "test": "7-01",
        "updated": "2023-05-28 10:27:12"
      }
    }
  ]
}
```

### **Error 401**

```problem+json
{
  "type": "Invalid authorization token",
  "title": "Unauthorized",
  "status": "401",
  "detail": "Request requires authorization."
}
```

### **Error 429**

```problem+json
{
  "type": "429-too-many-requests",
  "title": "Too Many Requests",
  "status": "429",
  "detail": "The client sent too many requests in a given timeframe; rate limiting is active."
}
```

<!-- tabs:end -->

<!-- div:right-panel -->

## Internal Workflow

Once the server receives such a request, it checks several things internally:

<div id="graph-container-1" class="graph-container" style="height:1000px"></div>

<!-- panels:end -->

<script>
G6.registerEdge('polyline-edge', {
  draw(cfg, group) {
    const { startPoint, endPoint } = cfg;
    const hgap = Math.abs(endPoint.x - startPoint.x);

    const path = [
      ['M', startPoint.x, startPoint.y],
      [
        'C',
        startPoint.x + hgap / 4,
        startPoint.y,
        endPoint.x - hgap / 2,
        endPoint.y,
        endPoint.x,
        endPoint.y,
      ],
    ];
    const shape = group.addShape('path', {
      attrs: {
        stroke: '#AAB7C4',
        path,
      },
      name: 'path-shape',
    });
    const midPoint = {
      x: (startPoint.x + endPoint.x) / 2,
      y: (startPoint.y + endPoint.y) / 2,
    };
    const label = group.addShape('text', {
      attrs: {
        text: cfg.label + '###########',
        x: midPoint.x,
        y: midPoint.y,
        textAlign: 'center',
        textBaseline: 'middle',
        fill: '#000',
        fontSize: 14,
      },
      name: 'label-shape',
    });
    return shape;
  },
});
renderWorkflow(document.getElementById('graph-container-1'), {
  nodes: [
    { id: 'init', ...workflowStart, label: 'server receives POST-request' },
    { id: 'checkToken', ...workflowDecision, label: 'does request contain token?' },
    { id: 'noTokenAction', ...workflowStep, label: "use default anonymous\nuser for auth" },
    { id: 'checkTokenValidity', ...workflowDecision, label: 'is token valid?' },
    { id: 'checkRateLimit', ...workflowDecision, label: "does request exceed\nrate limit?" },
    { id: 'loadUserGroups', ...workflowStep, label: 'load user groups' },
    { id: 'combineSearchQueries', ...workflowStep, label: "combine user search query\nwith security restricted queries" },
    { id: 'loadElementData', ...workflowStep, label: 'Load element data' },
    { id: 'error401', ...workflowEndError, label: "return 401" },
    { id: 'error404', ...workflowEndError, label: 'return 404' },
    { id: 'error429', ...workflowEndError, label: 'return 429' },
    { id: 'success200', ...workflowEndSuccess , label: "return 200"},
  ],
  edges: [
    { source: 'init', target: 'checkToken', label: '' },
    { source: 'checkToken', target: 'checkTokenValidity', label: 'yes' },
    { source: 'checkToken', target: 'noTokenAction', label: 'no' },
    { source: 'checkTokenValidity', target: 'checkRateLimit', label: 'yes' },
    { source: 'checkTokenValidity', target: 'error401', label: 'no' },
    { source: 'checkRateLimit', target: 'loadUserGroups', label: 'no' },
    { source: 'checkRateLimit', target: 'error429', label: 'yes' },
    { source: 'loadUserGroups', target: 'combineSearchQueries', label: '' },
    { source: 'combineSearchQueries', target: 'loadElementData', label: '' },
    { source: 'loadElementData', target: 'success200' },
    { source: 'checkElementAccess', target: 'error404', label: 'no' },
    { source: 'noTokenAction', target: 'checkRateLimit', label: '' }
  ],
}, 'TB');
</script>
