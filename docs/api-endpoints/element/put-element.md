# <span class="method-put">PUT</span>` /<uuid> -` Replace Element

<!-- panels:start -->
<!-- div:left-panel -->

Replaces the data of an individual data element.

> [!ATTENTION]
> The schema of the request body will be changed with the release of version 0.3.0, which is a **breaking change**.  
> See [issue #113](https://github.com/ember-nexus/api/issues/113) for details.

> [!NOTE]
> Some properties of internally used nodes and relations can not be changed directly, doing so will throw errors.

## Request Parameters

This endpoint does not require parameters.

## Request Headers

<div class="table-request-headers">

| Header          | Description                                                                                                       | Required | Default |
|-----------------|-------------------------------------------------------------------------------------------------------------------|----------|---------|
| `Authorization` | Contains an authentication token. <br />See [authentication](/concepts/authentication) for details.               | no       | -       |
| `Content-Type`  | Can be used to explicitly define the content type of the uploaded data. If specified, must be `application/json`. | no       | -       |

</div>

## Response Headers

<div class="table-response-headers">

| Header     | Description                                         | Default |
|------------|-----------------------------------------------------| ------- |
| `Location` | Contains the absolute path of the replaced element. | -       |

</div>

## Request Body

The posted request must be a valid JSON document. **The whole JSON document is used as the element's new data source**.

```json
{
  "examplePropertyName": "example property value",
  "object": {
    "hello": "world :D"
  }
}
```

## Request Example

Nodes:

```bash
curl \
  -X PUT \
  -H "Content-Type: application/json" \
  -d '{"hello": "world :D"}' \
  https://api.localhost/7b80b203-2b82-40f5-accd-c7089fe6114e
```

<!-- tabs:start -->

### **🟢 Success 204**

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](./put-element/204-response-header.txt ':include :type=code')

Success response does not have a response body.  
Note that the UUID of the replaced element is written in the `Location`-header.

### **🔴 Error 400**

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](./put-element/400-response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](./put-element/400-response-body.json ':include :type=code problem+json')

### **🔴 Error 401**

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](./put-element/401-response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](./put-element/401-response-body.json ':include :type=code problem+json')

### **🔴 Error 429**

<div class="code-title">Response Headers</div>

[Response Body](./put-element/429-response-header.txt ':include :type=code')

<div class="code-title">Response Body</div>

[Response Body](./put-element/429-response-body.json ':include :type=code problem+json')

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
    { id: 'init', ...workflowStart, label: 'server receives PUT-request' },
    { id: 'checkToken', ...workflowDecision, label: 'does request contain token?' },
    { id: 'noTokenAction', ...workflowStep, label: "use default anonymous\nuser for auth" },
    { id: 'checkTokenValidity', ...workflowDecision, label: 'is token valid?' },
    { id: 'checkRateLimit', ...workflowDecision, label: "does request exceed\nrate limit?" },
    { id: 'checkAccess', ...workflowDecision, label: "has user UPDATE access?" },
    { id: 'checkExistence', ...workflowDecision, label: "does element exist?" },
    { id: 'resetProperties', ...workflowStep, label: "reset properties" },
    { id: 'setNewProperties', ...workflowStep, label: "set new properties" },
    { id: 'flush', ...workflowStep, label: "flush" },
    { id: 'error404', ...workflowEndError, label: "return 404" },
    { id: 'error401', ...workflowEndError, label: "return 401" },
    { id: 'success204', ...workflowEndSuccess , label: "return 204"},
    { id: 'error429', ...workflowEndError, label: 'return 429' },
  ],
  edges: [
    { source: 'init', target: 'checkToken', label: '' },
    { source: 'checkToken', target: 'noTokenAction', label: 'no' },
    { source: 'checkToken', target: 'checkTokenValidity', label: 'yes' },
    { source: 'checkTokenValidity', target: 'checkRateLimit', label: 'yes' },
    { source: 'checkTokenValidity', target: 'error401', label: 'no' },
    { source: 'checkRateLimit', target: 'checkAccess', label: 'no' },
    { source: 'checkRateLimit', target: 'error429', label: 'yes' },
    { source: 'noTokenAction', target: 'checkRateLimit', label: '' },
    { source: 'checkAccess', target: 'checkExistence', label: 'yes' },
    { source: 'checkAccess', target: 'error404', label: 'no' },
    { source: 'checkExistence', target: 'resetProperties', label: 'yes' },
    { source: 'checkExistence', target: 'error404', label: 'no' },
    { source: 'resetProperties', target: 'setNewProperties' },
    { source: 'setNewProperties', target: 'flush' },
    { source: 'flush', target: 'success204' }
  ],
}, 'TB');
</script>
