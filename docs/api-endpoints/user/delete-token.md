# <span class="method-delete">DELETE</span>` /token -` Delete Token

<!-- panels:start -->
<!-- div:left-panel -->

Deletes the currently used token.

## Request Example

```bash
curl \
  -X DELETE
  -H "Authorization: Bearer secret-token:PIPeJGUt7c00ENn8a5uDlc" \
  https://api.localhost/token
```

<!-- tabs:start -->

### **🟢 Success 204**

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](./delete-token/204-response-header.txt ':include :type=code')

Success response does not have a return body.

### **🔴 Error 401**

This error can only be thrown if the token is invalid or if there is no default anonymous user.

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](./delete-token/401-response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](./delete-token/401-response-body.json ':include :type=code problem+json')

### **🔴 Error 429**

<div class="code-title">Response Headers</div>

[Response Body](./delete-token/429-response-header.txt ':include :type=code')

<div class="code-title">Response Body</div>

[Response Body](./delete-token/429-response-body.json ':include :type=code problem+json')

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
    { id: 'init', ...workflowStart, label: 'server receives DELETE-request' },
    { id: 'checkToken', ...workflowDecision, label: 'does request contain token?' },
    { id: 'checkTokenValidity', ...workflowDecision, label: 'is token valid?' },
    { id: 'checkRateLimit', ...workflowDecision, label: "does request exceed\nrate limit?" },
    { id: 'deleteToken', ...workflowStep, label: 'delete token' },
    { id: 'error401', ...workflowEndError, label: "return 401" },
    { id: 'error429', ...workflowEndError, label: 'return 429' },
    { id: 'success204', ...workflowEndSuccess , label: "return 204"},
  ],
  edges: [
    { source: 'init', target: 'checkToken', label: '' },
    { source: 'checkToken', target: 'checkTokenValidity', label: 'yes' },
    { source: 'checkToken', target: 'error401', label: 'no' },
    { source: 'checkTokenValidity', target: 'checkRateLimit', label: 'yes' },
    { source: 'checkTokenValidity', target: 'error401', label: 'no' },
    { source: 'checkRateLimit', target: 'deleteToken', label: 'no' },
    { source: 'checkRateLimit', target: 'error429', label: 'yes' },
    { source: 'deleteToken', target: 'success204' },
  ],
}, 'TB');
</script>
