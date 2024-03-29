# <span class="title-url"><span class="method-post">POST</span>` /`</span><span class="title-human">Create Root Level Element Endpoint</span>

<!-- panels:start -->
<!-- div:left-panel -->

Creates a new element. If the element is a node, it is directly owned by the current user.

## Request Parameters

This endpoint does not require parameters.

## Request Headers

<div class="table-request-headers">

| Header          | Description                                                                                         | Required | Default |
|-----------------|-----------------------------------------------------------------------------------------------------|----------|---------|
| `Authorization` | Contains an authentication token. <br />See [authentication](/concepts/authentication) for details. | no       | -       |

</div>

## Response Headers

<div class="table-response-headers">

| Header     | Description                                              | Default |
|------------|----------------------------------------------------------| ------- |
| `Location` | Contains the absolute path to the newly created element. | -       |

</div>

## Request Body

The posted request must be a valid JSON document.

The request must contain the following attributes:

- `type`: The type of element to be created. Internally used types might have restrictions.
- `id`: The elements UUID, optional. If not set, the API will generate one for you.
- `start`: The start node's UUID, only required for relations.
- `end`: The end node's UUID, only required for relations.
- `data`: Data related to the element in the form of an JSON object, optional. Restrictions might apply to internally
  used properties.

```json
{
  "type": "Demo",
  "data": {
    "hello": "world :D"
  }
}
```

## Request Example

Nodes:

```bash
curl \
  -X POST \
  -H "Content-Type: application/json" \
  -d '{"type": "Demo", "data": {"hello": "world :D"}}' \
  https://api.localhost/
```

Relations:

```bash
curl \
  -X POST \
  -H "Content-Type: application/json" \
  -d '{"type": "SOME_RELATION", "start": "f9a619e1-f3f5-4fbf-a80e-fa8f7dd88103", "end": "ecfe46ff-d3c7-4ca7-9b41-9be5476d36e0"}' \
  https://api.localhost/
```

<!-- tabs:start -->

### **🟢 Success 204**

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](./post-index/204-response-header.txt ':include :type=code')

Success response does not have a response body.  
Note that the UUID of the created element is written in the `Location`-header.

### **🔴 Error 400**

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](./post-index/400-response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](./post-index/400-response-body.json ':include :type=code problem+json')

### **🔴 Error 401**

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](./post-index/401-response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](./post-index/401-response-body.json ':include :type=code problem+json')

### **🔴 Error 404**

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](./post-index/404-response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](./post-index/404-response-body.json ':include :type=code problem+json')

### **🔴 Error 429**

<div class="code-title">Response Headers</div>

[Response Body](./post-index/429-response-header.txt ':include :type=code')

<div class="code-title">Response Body</div>

[Response Body](./post-index/429-response-body.json ':include :type=code problem+json')

<!-- tabs:end -->

<!-- div:right-panel -->

## Internal Workflow

Once the server receives such a request, it checks several things internally:

<div id="graph-container-1" class="graph-container" style="height:2200px"></div>

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
    { id: 'checkId', ...workflowDecision, label: "is id provided?" },
    { id: 'generateId', ...workflowStep, label: "generate new id" },
    { id: 'useProvidedId', ...workflowStep, label: "use provided id" },
    { id: 'checkType', ...workflowDecision, label: "is type provided?" },
    { id: 'checkStart', ...workflowDecision, label: "is start provided?" },
    { id: 'checkEnd', ...workflowDecision, label: "is end provided?" },
    { id: 'checkStartExistence', ...workflowDecision, label: "does start element exist?" },
    { id: 'checkStartAccess', ...workflowDecision, label: "has user CREATE access\nto start element?" },
    { id: 'checkStartNode', ...workflowDecision, label: "is start element node?" },
    { id: 'error404-1', ...workflowEndError, label: "return 404" },
    { id: 'error400-1', ...workflowEndError, label: "return 400" },
    { id: 'checkEndExistence', ...workflowDecision, label: "does end element exist?" },
    { id: 'checkEndAccess', ...workflowDecision, label: "has user READ access\nto end element?" },
    { id: 'checkEndNode', ...workflowDecision, label: "is end element node?" },
    { id: 'error404-2', ...workflowEndError, label: "return 404" },
    { id: 'error400-2', ...workflowEndError, label: "return 400" },
    { id: 'checkStartAndEndProperties', ...workflowDecision, label: "are both start and end\neither given or missing?" },
    { id: 'checkIdExistence', ...workflowDecision, label: "is element id free?" },
    { id: 'isNode', ...workflowDecision, label: "is element node?" },
    { id: 'createOwns', ...workflowStep, label: "add relation:\n(user)-[:OWNS]->(element)" },
    { id: 'createCreated', ...workflowStep, label: "add relation:\n(user)-[:CREATED]->(element)" },
    { id: 'createAndFlush', ...workflowStep, label: 'create and flush data' },
    { id: 'error400', ...workflowEndError, label: "return 400" },
    { id: 'error401', ...workflowEndError, label: "return 401" },
    { id: 'error404-2', ...workflowEndError, label: "return 404" },
    { id: 'error429', ...workflowEndError, label: 'return 429' },
    { id: 'success204', ...workflowEndSuccess , label: "return 204"},
  ],
  edges: [
    { source: 'init', target: 'checkToken', label: '' },
    { source: 'checkToken', target: 'noTokenAction', label: 'no' },
    { source: 'checkToken', target: 'checkTokenValidity', label: 'yes' },
    { source: 'checkTokenValidity', target: 'checkRateLimit', label: 'yes' },
    { source: 'checkTokenValidity', target: 'error401', label: 'no' },
    { source: 'checkRateLimit', target: 'checkId', label: 'no' },
    { source: 'checkRateLimit', target: 'error429', label: 'yes' },
    { source: 'checkId', target: 'generateId', label: 'no' },
    { source: 'checkId', target: 'useProvidedId', label: 'yes' },
    { source: 'generateId', target: 'checkType' },
    { source: 'useProvidedId', target: 'checkType' },
    { source: 'checkType', target: 'checkStart', label: 'yes' },
    { source: 'checkType', target: 'error400', label: 'no' },
    { source: 'checkStart', target: 'checkEnd', label: 'no' },
    { source: 'checkStart', target: 'checkStartExistence', label: 'yes' },
    { source: 'checkStartExistence', target: 'checkStartAccess', label: 'yes' },
    { source: 'checkStartAccess', target: 'checkStartNode', label: 'yes' },
    { source: 'checkStartNode', target: 'checkEnd', label: 'yes' },
    { source: 'checkStartExistence', target: 'error404-1', label: 'no' },
    { source: 'checkStartAccess', target: 'error404-1', label: 'no' },
    { source: 'checkStartNode', target: 'error400-1', label: 'no' },
    { source: 'checkEnd', target: 'checkStartAndEndProperties', label: 'no' },
    { source: 'checkEnd', target: 'checkEndExistence', label: 'yes' },
    { source: 'checkEndExistence', target: 'checkEndAccess', label: 'yes' },
    { source: 'checkEndAccess', target: 'checkEndNode', label: 'yes' },
    { source: 'checkEndExistence', target: 'error404-2', label: 'no' },
    { source: 'checkEndAccess', target: 'error404-2', label: 'no' },
    { source: 'checkEndNode', target: 'error400-2', label: 'no' },
    { source: 'checkEndNode', target: 'checkStartAndEndProperties', label: 'yes' },
    { source: 'checkStartAndEndProperties', target: 'checkIdExistence', label: 'yes' },
    { source: 'checkStartAndEndProperties', target: 'error400-2', label: 'no' },
    { source: 'checkIdExistence', target: 'isNode', label: 'yes' },
    { source: 'checkIdExistence', target: 'error400-2', label: 'no' },
    { source: 'isNode', target: 'createAndFlush', label: 'no' },
    { source: 'isNode', target: 'createOwns', label: 'yes' },
    { source: 'createOwns', target: 'createCreated' },
    { source: 'createCreated', target: 'createAndFlush' },
    { source: 'createAndFlush', target: 'success204' },
    { source: 'noTokenAction', target: 'checkRateLimit', label: '' }
  ],
}, 'TB');
</script>
