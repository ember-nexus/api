# <span class="method-delete">DELETE</span>` /<uuid> -` Delete Element

<!-- panels:start -->
<!-- div:left-panel -->

Deletes a single element. If the deleted element is a node, all connected relationships are deleted.

!> **Note**: In order to avoid orphaned nodes, children need to be deleted first or get other parents added.  
This behaviour might be changed, see issue [#64: HTTP DELETE /&lt;uuid&gt; - DeleteElementController](https://github.com/ember-nexus/api/issues/64).

## Request Example

```bash
curl \
  -X DELETE
  -H "Authorization: Bearer secret-token:PIPeJGUt7c00ENn8a5uDlc" \
  https://api.localhost/2f99440e-ca4c-4e83-bf86-1cd27a4b1b70
```

<!-- tabs:start -->

### **Success 204**

The element is now deleted. No content is returned.

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

### **Error 404**

Error 404 is thrown if the element to be deleted does not exist, or if the use does not have permissions to delete the
element.

```problem+json
{
  "type": "Invalid authorization token",
  "title": "wip",
  "status": "404",
  "detail": "wip"
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
    { id: 'init', ...workflowStart, label: 'server receives DELETE-request' },
    { id: 'checkToken', ...workflowDecision, label: 'does request contain token?' },
    { id: 'noTokenAction', ...workflowStep, label: "use default anonymous\nuser for auth" },
    { id: 'checkTokenValidity', ...workflowDecision, label: 'is token valid?' },
    { id: 'checkRateLimit', ...workflowDecision, label: "does request exceed\nrate limit?" },
    { id: 'checkExistence', ...workflowDecision, label: 'does element exist?' },
    { id: 'checkAccess', ...workflowDecision, label: 'has user permission\nto delete element?' },
    { id: 'deleteElement', ...workflowStep, label: 'delete element' },
    { id: 'error401', ...workflowEndError, label: "return 401" },
    { id: 'error404', ...workflowEndError, label: "return 404" },
    { id: 'error429', ...workflowEndError, label: 'return 429' },
    { id: 'success204', ...workflowEndSuccess , label: "return 204"},
  ],
  edges: [
    { source: 'init', target: 'checkToken', label: '' },
    { source: 'checkToken', target: 'checkTokenValidity', label: 'yes' },
    { source: 'checkToken', target: 'noTokenAction', label: 'no' },
    { source: 'checkTokenValidity', target: 'checkRateLimit', label: 'yes' },
    { source: 'checkTokenValidity', target: 'error401', label: 'no' },
    { source: 'checkRateLimit', target: 'checkExistence', label: 'no' },
    { source: 'checkRateLimit', target: 'error429', label: 'yes' },
    { source: 'checkExistence', target: 'checkAccess', label: 'yes' },
    { source: 'checkExistence', target: 'error404', label: 'no' },
    { source: 'checkAccess', target: 'deleteElement', label: 'yes' },
    { source: 'checkAccess', target: 'error404', label: 'no' },
    { source: 'deleteElement', target: 'success204' },
    { source: 'noTokenAction', target: 'checkRateLimit', label: '', type2: 'polyline-edge' }
  ],
}, 'TB');
</script>
