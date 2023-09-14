# <span class="method-get">GET</span>` /instance-configuration -` Get Instance Configuration

<!-- panels:start -->
<!-- div:left-panel -->

Returns instance specific configurations.

Endpoint can be disabled, see
[application configuration](/getting-started/configuration?id=application-configuration) for details.

## Request Example

```bash
curl https://api.localhost/instance-configuration
```

<!-- tabs:start -->

### **Success 200**

```json
{
  "version": "0.1.0",
  "pageSize": {
    "min": 5,
    "default": 25,
    "max": 100
  },
  "register": {
    "enabled": true,
    "uniqueIdentifier": "email",
    "uniqueIdentifierRegex": false
  }
}
```

### **Error 403**

```problem+json
{
  "type": "403-forbidden",
  "title": "Forbidden",
  "status": "403",
  "detail": "Client does not have permissions to perform action."
}
```

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
    { id: 'init', ...workflowStart, label: 'server receives GET-request' },
    { id: 'checkEndpointEnabled', ...workflowDecision, label: 'is endpoint enabled?' },
    { id: 'loadConfiguration', ...workflowStep, label: 'load instance configuration' },
    { id: 'error403', ...workflowEndError, label: "return 403" },
    { id: 'success200', ...workflowEndSuccess , label: "return 200"},
  ],
  edges: [
    { source: 'init', target: 'checkEndpointEnabled', label: '' },
    { source: 'checkEndpointEnabled', target: 'loadConfiguration', label: 'yes' },
    { source: 'checkEndpointEnabled', target: 'error403', label: 'no' },
    { source: 'loadConfiguration', target: 'success200' },
  ],
}, 'TB');
</script>
