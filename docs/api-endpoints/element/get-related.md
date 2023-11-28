# <span class="method-get">GET</span>` /<uuid>/related -` Get Related

<!-- panels:start -->
<!-- div:left-panel -->

Returns all nodes related to the current node.  
Returned data is paginated, and each page contains all relations between the node and the returned nodes. Includes
parents and children.

## Request Parameters

- `page`: Integer, number of the page to be returned. The first page is `1`, which is also the default if not explicitly
  set.
- `pageSize`: Integer, is the upper limit of nodes to be returned. Limited by the APIs instance configuration. By
  default, 25 and can be set between 5 and 100.

## Request Example

```bash
curl \
  -H "Authorization: Bearer secret-token:PIPeJGUt7c00ENn8a5uDlc" \
  https://api.localhost/72e6a603-34ec-47d9-84cb-a33233977a3c/related
```

<!-- tabs:start -->

### **🟢 Success 200**

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](./get-related/200-response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](./get-related/200-response-body.json ':include :type=code')

### **🔴 Error 401**

This error can only be thrown if the token is invalid or if there is no default anonymous user.

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](./get-related/401-response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](./get-related/401-response-body.json ':include :type=code problem+json')

### **🔴 Error 404**

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](./get-element/404-response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](./get-element/404-response-body.json ':include :type=code problem+json')

### **🔴 Error 429**

<div class="code-title">Response Headers</div>

[Response Body](./get-related/429-response-header.txt ':include :type=code')

<div class="code-title">Response Body</div>

[Response Body](./get-related/429-response-body.json ':include :type=code problem+json')

<!-- tabs:end -->

<!-- div:right-panel -->

## Internal Workflow

Once the server receives such a request, it checks several things internally:

<div id="graph-container-1" class="graph-container" style="height:1200px"></div>

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
    { id: 'checkElementExistence', ...workflowDecision, label: "does element exist?" },
    { id: 'checkRelation', ...workflowDecision, label: "is element relation?" },
    { id: 'checkAccess', ...workflowDecision, label: "has user access?" },
    { id: 'loadElementsData', ...workflowStep, label: 'load related' },
    { id: 'error401', ...workflowEndError, label: "return 401" },
    { id: 'error404', ...workflowEndError, label: "return 404" },
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
    { source: 'checkElementExistence', target: 'checkRelation', label: 'yes' },
    { source: 'checkElementExistence', target: 'error404', label: 'no' },
    { source: 'checkRelation', target: 'error404', label: 'yes' },
    { source: 'checkRelation', target: 'checkAccess', label: 'no' },
    { source: 'checkAccess', target: 'loadElementsData', label: 'yes' },
    { source: 'checkAccess', target: 'error404', label: 'no' },
    { source: 'loadElementsData', target: 'success200' },
    { source: 'noTokenAction', target: 'checkRateLimit', label: '', type2: 'polyline-edge' }
  ],
}, 'TB');
</script>
