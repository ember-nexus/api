# GET /&lt;uuid&gt; - Get Element

<!-- panels:start -->
<!-- div:left-panel -->

The get element endpoint at `GET /<uuid>` is used to retrieve the data of a single element, which can be either
a node or a relationship.

## Request Example

```bash
curl \
  -H "Authorization: Bearer secret-token:PIPeJGUt7c00ENn8a5uDlc" \
  https://api.localhost/74a8fcd9-6cb0-4b0d-8d42-0b6c3c54d1ac
```

<!-- tabs:start -->

### **Success 200**

Response for nodes:

```json
{
  "type": "Comment",
  "id": "74a8fcd9-6cb0-4b0d-8d42-0b6c3c54d1ac",
  "data": {
    "content": "Blue is one of the three primary colours in the RYB colour model ...",
    "created": "2023-08-09 21:17:16",
    "name": "Blue",
    "updated": "2023-08-09 21:17:16"
  }
}
```

Response for relations:

```json
{
  "type": "HAS_DATA",
  "id": "904a5f37-785e-428d-96ba-4fa58cd2bea8",
  "start": "ce0fde7f-851a-4933-bd1b-8d8a12f082f5",
  "end": "1c7e0a52-b0dc-441a-9fbc-9e30bedbf812",
  "data": {
    "name": "Some data relation",
    "description": "demo"
  }
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

### **Error 404**

```problem+json
{
  "type": "404-not-found",
  "title": "Not Found",
  "status": "404",
  "detail": "The requested resource was not found."
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
