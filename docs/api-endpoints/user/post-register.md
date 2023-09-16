# <span class="method-post">POST</span>` /register -` Register New Account

<!-- panels:start -->
<!-- div:left-panel -->

Endpoint for registering / creating new accounts.

The endpoint can be disabled; see
[application configuration](/getting-started/configuration?id=application-configuration) for details.

## Request Body

The posted request must be a valid JSON document.

The request must contain the following attributes:

- `type`: Containing the content "User". No other values are currently possible.
- `password`: The plain text password of the new user. It can contain any string and will be hashed internally.
  Whitespace at the start or end of the string will **not** be removed, though it is discouraged.  
  No password complexity check is performed.
- `data.<identifier>`: By default, `data.email` must contain a new unique string. While not required, keeping the
  content within 256 bytes is encouraged. Optional limits might be added at a later time.  
  The required identifier name is returned by the
  [instance configuration endpoint](/api-endpoints/get-instance-configuration) and in error messages.

```json
{
  "type": "User",
  "password": "1234",
  "data": {
    "<identifier>": "test@example.com"
  }
}
```

## Request Example

```bash
curl \
  -X POST \
  -H "Content-Type: application/json" \
  -d '{"type": "User", "password": "1234", "data": {"email": "test@example.com"}}' \
  https://api.localhost/register
```

<!-- tabs:start -->

### **🟢 Success 201**

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](./post-register/201-response-header.txt ':include :type=code')

Success response does not have a return body. The location of the new user, containing the user's UUID, is written in
the `Location` header.

### **🔴 Error 400**

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](./post-register/400-response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](./post-register/400-response-body.json ':include :type=code problem+json')

### **🔴 Error 403**

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](./post-register/403-response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](./post-register/403-response-body.json ':include :type=code problem+json')

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
    { id: 'init', ...workflowStart, label: 'server receives POST-request' },
    { id: 'checkEndpointEnabled', ...workflowDecision, label: 'is endpoint enabled?' },
    { id: 'checkPassword', ...workflowDecision, label: 'is password given?' },
    { id: 'checkType', ...workflowDecision, label: 'is type given?' },
    { id: 'checkTypeContent', ...workflowDecision, label: 'is type equal to "User"?' },
    { id: 'checkIdentifier', ...workflowDecision, label: "is identifier given?" },
    { id: 'checkIdentifierUnique', ...workflowDecision, label: 'is identifier unique?' },
    { id: 'createUser', ...workflowStep, label: "create user" },
    { id: 'error400', ...workflowEndError, label: "return 400" },
    { id: 'error403', ...workflowEndError, label: 'return 403' },
    { id: 'success201', ...workflowEndSuccess , label: "return 201"},
  ],
  edges: [
    { source: 'init', target: 'checkEndpointEnabled', label: '' },
    { source: 'checkEndpointEnabled', target: 'checkPassword', label: 'yes' },
    { source: 'checkEndpointEnabled', target: 'error403', label: 'no' },
    { source: 'checkPassword', target: 'checkType', label: 'yes' },
    { source: 'checkPassword', target: 'error400', label: 'no' },
    { source: 'checkType', target: 'checkTypeContent', label: 'yes' },
    { source: 'checkType', target: 'error400', label: 'no' },
    { source: 'checkTypeContent', target: 'checkIdentifier', label: 'yes' },
    { source: 'checkTypeContent', target: 'error400', label: 'no' },
    { source: 'checkIdentifier', target: 'checkIdentifierUnique', label: 'yes' },
    { source: 'checkIdentifier', target: 'error400', label: 'no' },
    { source: 'checkIdentifierUnique', target: 'createUser', label: 'yes' },
    { source: 'checkIdentifierUnique', target: 'error400', label: 'no' },
    { source: 'createUser', target: 'success201', label: '' },
  ],
}, 'TB');
</script>
