# Scenario 2-01: Immediate Node Ownership

Users which immediately own a node, have full access to it.

<div id="graph" class="graph-container" style="height:300px"></div>

<script>
renderGraph(document.getElementById('graph'), {
  nodes: [
    { id: 'user', ...userNode },
    { id: 'data', ...dataNode },
  ],
  edges: [
    { source: 'user', target: 'data', label: 'OWNS' },
  ]
});
</script>
