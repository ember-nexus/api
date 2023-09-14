# <span class="method-get">GET</span>` / -` Get Index

<!-- panels:start -->
<!-- div:left-panel -->

Returns all top level elements, to which the current user has direct access.

## Request Example

```bash
curl \
  -H "Authorization: Bearer secret-token:PIPeJGUt7c00ENn8a5uDlc" \
  https://api.localhost/
```

<!-- tabs:start -->

### **Success 200**

```json
{
  "type": "_PartialCollection",
  "id": "/",
  "totalNodes": 2,
  "links": {
    "first": "/",
    "previous": null,
    "next": null,
    "last": "/"
  },
  "nodes": [
    {
      "type": "Sphere",
      "id": "7b80b203-2b82-40f5-accd-c7089fe6114e",
      "data": {
        "created": "2023-08-09 21:17:16",
        "name": "Comment",
        "updated": "2023-08-09 21:17:16"
      }
    },
    {
      "type": "Token",
      "id": "e3b81351-fe0c-4f8f-ad22-78b6157edde8",
      "data": {
        "created": "2023-08-09 21:17:16",
        "updated": "2023-08-09 21:17:16"
      }
    }
  ],
  "relations": []
}
```

### **Error 401**

This error can only be thrown, if the token is invalid or if there is no default anonymous user.

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
    { id: 'init', ...workflowStart, label: 'server receives GET-request' },
    { id: 'checkToken', ...workflowDecision, label: 'does request contain token?' },
    { id: 'noTokenAction', ...workflowStep, label: "use default anonymous\nuser for auth" },
    { id: 'checkTokenValidity', ...workflowDecision, label: 'is token valid?' },
    { id: 'checkRateLimit', ...workflowDecision, label: "does request exceed\nrate limit?" },
    { id: 'loadElementsData', ...workflowStep, label: 'Load root level elements' },
    { id: 'error401', ...workflowEndError, label: "return 401" },
    { id: 'error429', ...workflowEndError, label: 'return 429' },
    { id: 'success200', ...workflowEndSuccess , label: "return 200"},
  ],
  edges: [
    { source: 'init', target: 'checkToken', label: '' },
    { source: 'checkToken', target: 'checkTokenValidity', label: 'yes' },
    { source: 'checkToken', target: 'noTokenAction', label: 'no' },
    { source: 'checkTokenValidity', target: 'checkRateLimit', label: 'yes' },
    { source: 'checkTokenValidity', target: 'error401', label: 'no' },
    { source: 'checkRateLimit', target: 'loadElementsData', label: 'no' },
    { source: 'checkRateLimit', target: 'error429', label: 'yes' },
    { source: 'checkElementExistence', target: 'checkElementAccess', label: 'yes' },
    { source: 'checkElementAccess', target: 'loadElementsData', label: 'yes' },
    { source: 'loadElementsData', target: 'success200' },
    { source: 'noTokenAction', target: 'checkRateLimit', label: '', type2: 'polyline-edge' }
  ],
}, 'TB');
</script>
