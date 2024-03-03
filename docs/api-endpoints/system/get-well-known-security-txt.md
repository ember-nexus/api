# <span class="title-url"><span class="method-get">GET</span>` /.well-known/security.txt`</span><span class="title-human">Get Well Known security.txt Endpoint</span>

<!-- panels:start -->
<!-- div:left-panel -->

Returns the configured security.txt file.

See [https://securitytxt.org/](https://securitytxt.org/) for details regarding this file standard.

## Request Example

```bash
curl https://api.localhost/.well-known/security.txt
```

<!-- tabs:start -->

### **🟢 Success 200**

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](./get-well-known-security-txt/200-response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](./get-well-known-security-txt/200-response-body.txt ':include :type=code')

<!-- tabs:end -->

<!-- div:right-panel -->

## Internal Workflow

The server returns the configured file directly.

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
    { id: 'success200', ...workflowEndSuccess , label: "return 200"},
  ],
  edges: [
    { source: 'init', target: 'success200', label: '' },
  ],
}, 'TB');
</script>
