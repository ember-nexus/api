# Scenario 3-03: Path Node Ownership

Users of Groups which are connected to a node through multiple chained `OWNS` relations have full access to the node:

<div id="graph" class="graph-container" style="height:300px"></div>

<script>
renderGraph(document.getElementById('graph'), {
  nodes: [
    { id: 'user', ...userNode },
    { id: 'group', ...groupNode },
    { id: 'data1', ...dataNode, label: 'Data 1' },
    { id: 'data2', ...dataNode, label: 'Data 2' },
    { id: 'data3', ...dataNode, label: 'Data 3' },
  ],
  edges: [
    { source: 'user', target: 'group', label: 'IS_IN_GROUP' },
    { source: 'group', target: 'data1', label: 'OWNS' },
    { source: 'data1', target: 'data2', label: 'OWNS' },
    { source: 'data2', target: 'data3', label: 'OWNS' },
  ]
});
</script>
