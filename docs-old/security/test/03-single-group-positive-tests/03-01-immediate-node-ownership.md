# Scenario 3-01: Immediate Node Ownership

Users of groups which immediately own a node, have full access to it.

<div id="graph" class="graph-container" style="height:300px"></div>

<script>
renderGraph(document.getElementById('graph'), {
  nodes: [
    { id: 'user', ...userNode },
    { id: 'group', ...groupNode },
    { id: 'data', ...dataNode },
  ],
  edges: [
    { source: 'user', target: 'group', label: 'IS_IN_GROUP' },
    { source: 'group', target: 'data', label: 'OWNS' },
  ]
});
</script>
