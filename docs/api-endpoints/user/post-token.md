# <span class="title-url"><span class="method-post">POST</span>` /token`</span><span class="title-human">Create Token Endpoint</span>

<!-- panels:start -->
<!-- div:left-panel -->

> [!NOTE]
> This endpoint's request body received a breaking change with version [0.1.6](https://github.com/ember-nexus/api/releases/tag/0.1.6).
> The previous variant is deprecated and will be removed in version 0.2.0.
> Link to the old documentation: [POST /token (old)](/api-endpoints/user/post-token-old.md).

Endpoint for creating new tokens.

The endpoint can be configured; see
[application configuration](/getting-started/configuration?id=application-configuration) for details.

## Request Body

The posted request must be a valid JSON document.

The request must contain the following attributes:

- `type`: Containing the content `Token`. No other values are currently possible.
- `uniqueUserIdentifier`: Some attribute uniquely identifying a user, normally an email address. Can be configured.
- `password`: The plain text password of the user.
- `data`: Object of properties, optional.

```json
{
  "type": "Token",
  "uniqueUserIdentifier": "test@localhost.dev",
  "password": "1234",
  "data": {
    "key": "value"
  }
}
```

## Request Example

```bash
curl \
  -X POST \
  -H "Content-Type: application/json" \
  -d '{"type": "Token", "uniqueUserIdentifier": "test@localhost.dev", "password": "1234"}' \
  https://api.localhost/token
```

<!-- tabs:start -->

### **🟢 Success 200**

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](./post-token/200-response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](./post-token/200-response-body.json ':include :type=code')

### **🔴 Error 400**

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](./post-token/400-response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](./post-token/400-response-body.json ':include :type=code problem+json')

### **🔴 Error 401**

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](./post-token/401-response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](./post-token/401-response-body.json ':include :type=code problem+json')

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
    { id: 'checkType', ...workflowDecision, label: 'is type given?' },
    { id: 'checkTypeContent', ...workflowDecision, label: 'is type equal\nto "Token"?' },
    { id: 'checkUniqueUserIdentifierProperty', ...workflowDecision, label: 'is uniqueUserIdentifier\ngiven?' },
    { id: 'checkPasswordProperty', ...workflowDecision, label: "is password given?" },
    { id: 'checkCredentials', ...workflowDecision, label: 'are credentials ok?' },
    { id: 'createToken', ...workflowStep, label: "create token" },
    { id: 'error400', ...workflowEndError, label: "return 400" },
    { id: 'error401', ...workflowEndError, label: 'return 401' },
    { id: 'success200', ...workflowEndSuccess , label: "return 200"},
  ],
  edges: [
    { source: 'init', target: 'checkType', label: '' },
    { source: 'checkType', target: 'checkTypeContent', label: 'yes' },
    { source: 'checkType', target: 'error400', label: 'no' },
    { source: 'checkTypeContent', target: 'checkUniqueUserIdentifierProperty', label: 'yes' },
    { source: 'checkTypeContent', target: 'error400', label: 'no' },
    { source: 'checkUniqueUserIdentifierProperty', target: 'checkPasswordProperty', label: 'yes' },
    { source: 'checkUniqueUserIdentifierProperty', target: 'error400', label: 'no' },
    { source: 'checkPasswordProperty', target: 'checkCredentials', label: 'yes' },
    { source: 'checkPasswordProperty', target: 'error400', label: 'no' },
    { source: 'checkCredentials', target: 'createToken', label: 'yes' },
    { source: 'checkCredentials', target: 'error401', label: 'no' },
    { source: 'createToken', target: 'success200', label: '' },
  ],
}, 'TB');
</script>
