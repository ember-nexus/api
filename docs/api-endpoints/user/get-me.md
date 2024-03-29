# <span class="title-url"><span class="method-get">GET</span>` /me`</span><span class="title-human">Get Me Endpoint</span>

<!-- panels:start -->
<!-- div:left-panel -->

Returns the associated data of the current user.  
This endpoint is an alias to `GET /<userUuid>` and is useful when the
current user's UUID is unknown.

## Request Example

```bash
curl \
  -H "Authorization: Bearer secret-token:PIPeJGUt7c00ENn8a5uDlc" \
  https://api.localhost/me
```

<!-- tabs:start -->

### **🟢 Success 200**

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](./get-me/200-response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](./get-me/200-response-body.json ':include :type=code')

### **🔴 Error 401**

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](./get-me/401-response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](./get-me/401-response-body.json ':include :type=code problem+json')

### **🔴 Error 429**

<div class="code-title">Response Headers</div>

[Response Body](./get-me/429-response-header.txt ':include :type=code')

<div class="code-title">Response Body</div>

[Response Body](./get-me/429-response-body.json ':include :type=code problem+json')

<!-- tabs:end -->

<!-- div:right-panel -->

## Internal Workflow

Once the server receives such a request, it checks several things internally:

<div id="graph-container-1" class="graph-container" style="height:800px"></div>

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
    { id: 'loadUserData', ...workflowStep, label: 'load user data' },
    { id: 'error401', ...workflowEndError, label: 'return 401' },
    { id: 'error429', ...workflowEndError, label: 'return 429' },
    { id: 'success200', ...workflowEndSuccess , label: "return 200"},
  ],
  edges: [
    { source: 'init', target: 'checkToken', label: '' },
    { source: 'checkToken', target: 'checkTokenValidity', label: 'yes' },
    { source: 'checkToken', target: 'noTokenAction', label: 'no' },
    { source: 'checkTokenValidity', target: 'checkRateLimit', label: 'yes' },
    { source: 'checkTokenValidity', target: 'error401', label: 'no' },
    { source: 'checkRateLimit', target: 'loadUserData', label: 'no' },
    { source: 'checkRateLimit', target: 'error429', label: 'yes' },
    { source: 'loadUserData', target: 'success200' },
    { source: 'noTokenAction', target: 'checkRateLimit', label: '' }
  ],
}, 'TB');
</script>
