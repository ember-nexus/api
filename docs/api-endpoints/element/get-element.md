# <span class="title-url"><span class="method-get">GET</span>` /<uuid>`</span><span class="title-human">Get Element Endpoint</span>

<!-- panels:start -->
<!-- div:left-panel -->

The get element endpoint at `GET /<uuid>` is used to retrieve the data of a single element, which can be either
a node or a relationship.

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

| Header | Description                                                                                                                                                          | Default |
| ------ |----------------------------------------------------------------------------------------------------------------------------------------------------------------------| ------- |
| `Etag` | The `Etag`, short for "entity tag", is used to identify a particular version of the element for caching purposes.<br />See [caching](/concepts/caching) for details. | -       |

</div>

## Request Example

```bash
curl \
  -H "Authorization: Bearer secret-token:PIPeJGUt7c00ENn8a5uDlc" \
  https://api.localhost/74a8fcd9-6cb0-4b0d-8d42-0b6c3c54d1ac
```

<!-- tabs:start -->

### **🟢 Success 200 - Node**

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](./get-element/200-node-response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](./get-element/200-node-response-body.json ':include :type=code')

### **🟢 Success 200 - Relation**

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](./get-element/200-relation-response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](./get-element/200-relation-response-body.json ':include :type=code')

### **🔴 Error 401**

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](./get-element/401-response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](./get-element/401-response-body.json ':include :type=code problem+json')

### **🔴 Error 404**

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](./get-element/404-response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](./get-element/404-response-body.json ':include :type=code problem+json')

### **🔴 Error 429**

<div class="code-title">Response Headers</div>

[Response Body](./get-element/429-response-header.txt ':include :type=code')

<div class="code-title">Response Body</div>

[Response Body](./get-element/429-response-body.json ':include :type=code problem+json')

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
    { id: 'init', ...workflowStart, label: 'server receives GET-request' },
    { id: 'checkToken', ...workflowDecision, label: 'does request contain token?' },
    { id: 'noTokenAction', ...workflowStep, label: "use default anonymous\nuser for auth" },
    { id: 'checkTokenValidity', ...workflowDecision, label: 'is token valid?' },
    { id: 'checkRateLimit', ...workflowDecision, label: "does request exceed\nrate limit?" },
    { id: 'checkElementExistence', ...workflowDecision, label: 'does element exist?' },
    { id: 'checkElementAccess', ...workflowDecision, label: "does user has\naccess to element?" },
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
    { source: 'checkRateLimit', target: 'checkElementExistence', label: 'no' },
    { source: 'checkRateLimit', target: 'error429', label: 'yes' },
    { source: 'checkElementExistence', target: 'checkElementAccess', label: 'yes' },
    { source: 'checkElementExistence', target: 'error404', label: 'no' },
    { source: 'checkElementAccess', target: 'loadElementData', label: 'yes' },
    { source: 'loadElementData', target: 'success200' },
    { source: 'checkElementAccess', target: 'error404', label: 'no' },
    { source: 'noTokenAction', target: 'checkRateLimit', label: '', type2: 'polyline-edge' }
  ],
}, 'TB');
</script>
