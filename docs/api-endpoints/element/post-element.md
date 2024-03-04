# <span class="title-url"><span class="method-post">POST</span>` /<uuid>`</span><span class="title-human">Create Element Endpoint</span>

<!-- panels:start -->
<!-- div:left-panel -->

Creates a new node. It is owned by the referenced node.

> [!NOTE]
> This endpoint can not create relations, as the behaviour of "owning a relation", i.e.
> `(user)-[:OWNS]->[relation]`, is undefined.

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
  https://api.localhost/7b80b203-2b82-40f5-accd-c7089fe6114e
```

<!-- tabs:start -->

### **🟢 Success 204**

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](./post-element/204-response-header.txt ':include :type=code')

Success response does not have a response body.  
Note that the UUID of the created element is written in the `Location`-header.

### **🔴 Error 400**

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](./post-element/400-response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](./post-element/400-response-body.json ':include :type=code problem+json')

### **🔴 Error 401**

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](./post-element/401-response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](./post-element/401-response-body.json ':include :type=code problem+json')

### **🔴 Error 429**

<div class="code-title">Response Headers</div>

[Response Body](./post-element/429-response-header.txt ':include :type=code')

<div class="code-title">Response Body</div>

[Response Body](./post-element/429-response-body.json ':include :type=code problem+json')

<!-- tabs:end -->

<!-- div:right-panel -->

## Internal Workflow

Once the server receives such a request, it checks several things internally:

<div id="graph-container-1" class="graph-container" style="height:2000px"></div>

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
    { id: 'checkParentExistence', ...workflowDecision, label: "does parent\nelement exist?" },
    { id: 'checkParentIsNode', ...workflowDecision, label: "is parent a node?" },
    { id: 'checkCreateAccessToParent', ...workflowDecision, label: "has user CREATE\naccess to parent?" },
    { id: 'error404', ...workflowEndError, label: "return 404" },
    { id: 'checkStartProperty', ...workflowDecision, label: "does body contain\nstart property?" },
    { id: 'checkEndProperty', ...workflowDecision, label: "does body contain\nend property?" },
    { id: 'error400-1', ...workflowEndError, label: "return 400" },
    { id: 'checkId', ...workflowDecision, label: "is id provided?" },
    { id: 'generateId', ...workflowStep, label: "generate new id" },
    { id: 'useProvidedId', ...workflowStep, label: "use provided id" },
    { id: 'checkType', ...workflowDecision, label: "is type provided?" },
    { id: 'error400-2', ...workflowEndError, label: "return 400" },
    { id: 'checkIdExistence', ...workflowDecision, label: "is element id free?" },
    { id: 'createOwns', ...workflowStep, label: "add relation:\n(parent)-[:OWNS]->(element)" },
    { id: 'createCreated', ...workflowStep, label: "add relation:\n(user)-[:CREATED]->(element)" },
    { id: 'createAndFlush', ...workflowStep, label: 'create and flush data' },
    { id: 'error401', ...workflowEndError, label: "return 401" },
    { id: 'error429', ...workflowEndError, label: 'return 429' },
    { id: 'success204', ...workflowEndSuccess , label: "return 204"},
  ],
  edges: [
    { source: 'init', target: 'checkToken', label: '' },
    { source: 'checkToken', target: 'noTokenAction', label: 'no' },
    { source: 'checkToken', target: 'checkTokenValidity', label: 'yes' },
    { source: 'checkTokenValidity', target: 'checkRateLimit', label: 'yes' },
    { source: 'checkTokenValidity', target: 'error401', label: 'no' },
    { source: 'checkRateLimit', target: 'checkParentExistence', label: 'no' },
    { source: 'checkRateLimit', target: 'error429', label: 'yes' },
    { source: 'checkParentExistence', target: 'checkParentIsNode', label: 'yes' },
    { source: 'checkParentExistence', target: 'error404', label: 'no' },
    { source: 'checkParentIsNode', target: 'checkCreateAccessToParent', label: 'yes' },
    { source: 'checkParentIsNode', target: 'error404', label: 'no' },
    { source: 'checkCreateAccessToParent', target: 'checkStartProperty', label: 'yes' },
    { source: 'checkCreateAccessToParent', target: 'error404', label: 'no' },
    { source: 'checkStartProperty', target: 'checkEndProperty', label: 'no' },
    { source: 'checkStartProperty', target: 'error400-1', label: 'yes' },
    { source: 'checkEndProperty', target: 'checkId', label: 'no' },
    { source: 'checkEndProperty', target: 'error400-1', label: 'yes' },
    { source: 'checkId', target: 'generateId', label: 'no' },
    { source: 'checkId', target: 'useProvidedId', label: 'yes' },
    { source: 'generateId', target: 'checkType' },
    { source: 'useProvidedId', target: 'checkType' },
    { source: 'checkType', target: 'checkIdExistence', label: 'yes' },
    { source: 'checkType', target: 'error400-2', label: 'no' },
    { source: 'checkIdExistence', target: 'createOwns', label: 'yes' },
    { source: 'checkIdExistence', target: 'error400-2', label: 'no' },
    { source: 'createOwns', target: 'createCreated' },
    { source: 'createCreated', target: 'createAndFlush' },
    { source: 'createAndFlush', target: 'success204' },
    { source: 'noTokenAction', target: 'checkRateLimit', label: '', type2: 'polyline-edge' }
  ],
}, 'TB');
</script>
