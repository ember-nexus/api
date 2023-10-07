# <span class="method-post">POST</span>` / -` Create Root Level Element


<!-- panels:start -->
<!-- div:left-panel -->

Creates a new data element. If the data element is a node, it is directly owned by the current user.

## Request Body

The posted request must be a valid JSON document.

The request must contain the following attributes:

- `type`: The type of element to be created. Internally used types might have restrictions.
- `id`: The elements UUID, optional. If not set, the API will generate one for you.
- `start`: The start node's UUID, only required for relations.
- `end`: The end node's UUID, only required for relations.
- `data`: Data related to the element in the form of an JSON object. Restrictions might apply to internally used
  properties. If no properties should be used, send an empty object, e.g. `"data": {}`;

```json
{
  "type": "Demo",
  "data": {
    "hello": "world :D"
  }
}
```

## Request Example

```bash
curl \
  -X POST \
  -H "Content-Type: application/json" \
  -d '{"type": "Demo", "data": {"hello": "world :D"}}' \
  https://api.localhost/
```

<!-- tabs:start -->

### **🟢 Success 204**

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](./post-index/204-response-header.txt ':include :type=code')

Success response does not have a response body. The UUID of the created element is written in the `Location`-header.

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

### **🔴 Error 403**

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](./post-index/403-response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](./post-index/403-response-body.json ':include :type=code problem+json')

### **🔴 Error 429**

<div class="code-title">Response Headers</div>

[Response Body](./post-index/429-response-header.txt ':include :type=code')

<div class="code-title">Response Body</div>

[Response Body](./post-index/429-response-body.json ':include :type=code problem+json')

<!-- tabs:end -->

<!-- div:right-panel -->

## Internal Workflow

Once the server receives such a request, it checks several things internally:

<div id="graph-container-1" class="graph-container" style="height:1400px"></div>

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
    { id: 'checkType', ...workflowDecision, label: 'is type given?' },
    { id: 'checkTypeContent', ...workflowDecision, label: "is type equal to\n\"ActionChangePassword\"?" },
    { id: 'checkCurrentPassword', ...workflowDecision, label: "is currentPassword given?" },
    { id: 'checkNewPassword', ...workflowDecision, label: 'is newPassword given?' },
    { id: 'checkUniqueIdentifier', ...workflowDecision, label: 'is unique identifier given?' },
    { id: 'checkNewPasswordDifferentToCurrentPassword', ...workflowDecision, label: "is new password different\nto old password?" },
    { id: 'checkUser', ...workflowDecision, label: 'does user exist?' },
    { id: 'checkAnonymousUser', ...workflowDecision, label: 'is anonymous user?' },
    { id: 'checkCurrentPasswordMatch', ...workflowDecision, label: 'does current password match?' },
    { id: 'changePassword', ...workflowStep, label: "change password" },
    { id: 'error400', ...workflowEndError, label: "return 400" },
    { id: 'error401', ...workflowEndError, label: "return 401" },
    { id: 'error403', ...workflowEndError, label: 'return 403' },
    { id: 'success204', ...workflowEndSuccess , label: "return 204"},
  ],
  edges: [
    { source: 'init', target: 'checkType', label: '' },
    { source: 'checkType', target: 'checkTypeContent', label: 'yes' },
    { source: 'checkType', target: 'error400', label: 'no' },
    { source: 'checkTypeContent', target: 'checkCurrentPassword', label: 'yes' },
    { source: 'checkTypeContent', target: 'error400', label: 'no' },
    { source: 'checkCurrentPassword', target: 'checkNewPassword', label: 'yes' },
    { source: 'checkCurrentPassword', target: 'error400', label: 'no' },
    { source: 'checkNewPassword', target: 'checkUniqueIdentifier', label: 'yes' },
    { source: 'checkNewPassword', target: 'error400', label: 'no' },
    { source: 'checkUniqueIdentifier', target: 'checkNewPasswordDifferentToCurrentPassword', label: 'yes' },
    { source: 'checkUniqueIdentifier', target: 'error400', label: 'no' },
    { source: 'checkNewPasswordDifferentToCurrentPassword', target: 'checkUser', label: 'yes' },
    { source: 'checkNewPasswordDifferentToCurrentPassword', target: 'error400', label: 'no' },
    { source: 'checkUser', target: 'checkAnonymousUser', label: 'yes' },
    { source: 'checkUser', target: 'error401', label: 'no' },
    { source: 'checkAnonymousUser', target: 'checkCurrentPasswordMatch', label: 'no' },
    { source: 'checkAnonymousUser', target: 'error403', label: 'yes' },
    { source: 'checkCurrentPasswordMatch', target: 'changePassword', label: 'yes' },
    { source: 'checkCurrentPasswordMatch', target: 'error401', label: 'no' },
    { source: 'changePassword', target: 'success204', label: '' },
  ],
}, 'TB');
</script>
