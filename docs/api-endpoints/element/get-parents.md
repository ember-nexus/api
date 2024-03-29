# <span class="title-url"><span class="method-get">GET</span>` /<uuid>/parents`</span><span class="title-human">Get Parents Endpoint</span>

<!-- panels:start -->
<!-- div:left-panel -->

Returns all parents of the specified node which the current user can access.  
Returned data is paginated; each page contains all relations between the node and the returned parents.

> [!NOTE]
> Usually, a node only has one parent, although the security system allows multiple ones.

## Request Parameters

<div class="table-request-parameters">

| Parameter  | Description                                                                                                           | Required | Default |
| ---------- |-----------------------------------------------------------------------------------------------------------------------|----------| ------- |
| `page`     | Specifies the page number to retrieve, starting with page 1.<br />See [pagination](/concepts/pagination) for details. | no       | 1       |
| `pageSize` | Defines the number of nodes returned in a single page.<br />See [pagination](/concepts/pagination) for details.       | no       | 25      |

</div>

## Request Headers

<div class="table-request-headers">

| Header          | Description                                                                                                                | Required | Default |
|-----------------|----------------------------------------------------------------------------------------------------------------------------|----------|---------|
| `Authorization` | Contains an authentication token. <br />See [authentication](/concepts/authentication) for details.                        | no       | -       |
| `If-Match`      | The `If-Match`-header can be used to make the `GET` request conditional.<br />See [caching](/concepts/caching) for details. | no       | -       |

</div>

## Response Headers

<div class="table-response-headers">

| Header | Description                                                                                                                                                          | Default |
| ------ |----------------------------------------------------------------------------------------------------------------------------------------------------------------------| ------- |
| `ETag` | The `ETag`, short for "entity tag", is used to identify a particular version of the element for caching purposes.<br />See [caching](/concepts/caching) for details. | -       |

</div>

## Request Example

```bash
curl \
  -H "Authorization: Bearer secret-token:PIPeJGUt7c00ENn8a5uDlc" \
  https://api.localhost/72e6a603-34ec-47d9-84cb-a33233977a3c/parents
```

<!-- tabs:start -->

### **🟢 Success 200**

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](./get-parents/200-response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](./get-parents/200-response-body.json ':include :type=code')

### **🟢 Redirect 304**

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](./get-index/304-response-header.txt ':include :type=code')

Redirect response does not have a response body.

### **🔴 Error 401**

This error can only be thrown if the token is invalid or if there is no default anonymous user.

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](./get-parents/401-response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](./get-parents/401-response-body.json ':include :type=code problem+json')

### **🔴 Error 404**

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](./get-element/404-response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](./get-element/404-response-body.json ':include :type=code problem+json')

### **🔴 Error 412**

Error 412 is thrown if the request header `If-Match` or `If-None-Match` is present and their precondition fails.

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](./delete-element/412-response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](./delete-element/412-response-body.json ':include :type=code problem+json')

### **🔴 Error 429**

<div class="code-title">Response Headers</div>

[Response Body](./get-parents/429-response-header.txt ':include :type=code')

<div class="code-title">Response Body</div>

[Response Body](./get-parents/429-response-body.json ':include :type=code problem+json')

<!-- tabs:end -->

<!-- div:right-panel -->

## Internal Workflow

Once the server receives such a request, it checks several things internally:

<div id="graph-container-1" class="graph-container" style="height:1700px"></div>

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
    { id: 'checkIfNoneMatchHeaderExists', ...workflowDecision, label: "does request contain\nIf-None-Match header?" },
    { id: 'checkIfNoneMatchHeaderMatches', ...workflowDecision, label: "does If-None-Match\nmatch ETag?" },
    { id: 'checkIfMatchHeaderExists', ...workflowDecision, label: "does request contain\nIf-Match header?" },
    { id: 'checkIfMatchHeaderMatches', ...workflowDecision, label: "does If-Match\nmatch ETag?" },
    { id: 'checkElementExistence', ...workflowDecision, label: "does element exist?" },
    { id: 'checkRelation', ...workflowDecision, label: "is element relation?" },
    { id: 'checkAccess', ...workflowDecision, label: "has user access?" },
    { id: 'loadElementsData', ...workflowStep, label: 'load parents' },
    { id: 'success200', ...workflowEndSuccess , label: "return 200"},
    { id: 'redirect304', ...workflowEndSuccess , label: "return 304"},
    { id: 'error401', ...workflowEndError, label: "return 401" },
    { id: 'error404', ...workflowEndError, label: "return 404" },
    { id: 'error412', ...workflowEndError, label: 'return 412' },
    { id: 'error429', ...workflowEndError, label: 'return 429' },
  ],
  edges: [
    { source: 'init', target: 'checkToken', label: '' },
    { source: 'checkToken', target: 'noTokenAction', label: 'no' },
    { source: 'checkToken', target: 'checkTokenValidity', label: 'yes' },
    { source: 'checkTokenValidity', target: 'checkRateLimit', label: 'yes' },
    { source: 'checkTokenValidity', target: 'error401', label: 'no' },
    { source: 'checkRateLimit', target: 'checkIfNoneMatchHeaderExists', label: 'no' },
    { source: 'checkRateLimit', target: 'error429', label: 'yes' },
    { source: 'checkIfNoneMatchHeaderExists', target: 'checkIfMatchHeaderExists', label: 'no' },
    { source: 'checkIfNoneMatchHeaderExists', target: 'checkIfNoneMatchHeaderMatches', label: 'yes' },
    { source: 'checkIfNoneMatchHeaderMatches', target: 'checkIfMatchHeaderExists', label: 'no' },
    { source: 'checkIfNoneMatchHeaderMatches', target: 'redirect304', label: 'yes' },
    { source: 'checkIfMatchHeaderExists', target: 'checkElementExistence', label: 'no' },
    { source: 'checkIfMatchHeaderExists', target: 'checkIfMatchHeaderMatches', label: 'yes' },
    { source: 'checkIfMatchHeaderMatches', target: 'checkElementExistence', label: 'yes' },
    { source: 'checkIfMatchHeaderMatches', target: 'error412', label: 'no' },
    { source: 'checkElementExistence', target: 'checkRelation', label: 'yes' },
    { source: 'checkElementExistence', target: 'error404', label: 'no' },
    { source: 'checkRelation', target: 'checkAccess', label: 'no' },
    { source: 'checkRelation', target: 'error404', label: 'yes' },
    { source: 'checkAccess', target: 'loadElementsData', label: 'yes' },
    { source: 'checkAccess', target: 'error404', label: 'no' },
    { source: 'loadElementsData', target: 'success200' },
    { source: 'noTokenAction', target: 'checkRateLimit', label: '' }
  ],
}, 'TB');
</script>
