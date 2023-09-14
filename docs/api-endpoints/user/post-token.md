# <span class="method-post">POST</span>` /token -` Create Token

<!-- panels:start -->
<!-- div:left-panel -->

Endpoint for creating new tokens.

Endpoint can be configured, see
[application configuration](/getting-started/configuration?id=application-configuration) for details.

## Request Body

The posted request must be a valid JSON document.

The request must contain the following attributes:

- `type`: Containing the content "Token". No other values are currently possible.
- `user`: The value for the user's identifying property, by default the user's email address.
- `password`: The plain text password of the user.
- `data`: Object of properties, optional.

```json
{
  "type": "Token",
  "user": "test@localhost.dev",
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
  -d '{"type": "Token", "user": "test@localhost.dev", "password": "1234"}' \
  https://api.localhost/token
```

<!-- tabs:start -->

### **Success 200**

```json
{
  "type": "_TokenResponse",
  "token": "secret-token:ERgAAnWl0CY8bQs0m11nZ3"
}
```

### **Error 400**

```problem+json
{
  "type": "400-bad-request",
  "title": "Bad Request",
  "status": 400,
  "detail": "Property 'user' must be set."
}
```

### **Error 401**

```problem+json
{
  "type": "401-unauthorized",
  "title": "Request does not contain valid token, or anonymous user is disabled.",
  "status": 401,
  "detail": ""
}
```

### **Error 500**

wip

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
    { id: 'init', ...workflowStart, label: 'server receives POST-request' },
    { id: 'checkType', ...workflowDecision, label: 'is type given?' },
    { id: 'checkTypeContent', ...workflowDecision, label: 'is type equal\nto "Token"?' },
    { id: 'checkUserProperty', ...workflowDecision, label: 'is user given?' },
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
    { source: 'checkTypeContent', target: 'checkUserProperty', label: 'yes' },
    { source: 'checkTypeContent', target: 'error400', label: 'no' },
    { source: 'checkUserProperty', target: 'checkPasswordProperty', label: 'yes' },
    { source: 'checkUserProperty', target: 'error400', label: 'no' },
    { source: 'checkPasswordProperty', target: 'checkCredentials', label: 'yes' },
    { source: 'checkPasswordProperty', target: 'error400', label: 'no' },
    { source: 'checkCredentials', target: 'createToken', label: 'yes' },
    { source: 'checkCredentials', target: 'error401', label: 'no' },
    { source: 'createToken', target: 'success200', label: '' },
  ],
}, 'TB');
</script>
