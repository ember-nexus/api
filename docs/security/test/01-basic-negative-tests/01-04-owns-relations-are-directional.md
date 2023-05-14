# Scenario 1-04: OWNS-relations are directional

`OWNS`-relationships are directional, i.e. users who have access to an owned element do not automatically have access to
its parents.

In the following example, user 1 has access to both data nodes, while user 2 can only access the second data node. The
`OWNS`-relationship between data 1 and data 2 is directional, i.e. can only grant the owners of data 1 access (user 1),
while the relationship is ignored for owners of data 2 (user 2):

<div id="graph" class="graph-container" style="height:400px"></div>

<script>
renderGraph(document.getElementById('graph'), {
  nodes: [
    { id: 'user1', ...userNode, label: 'User 1' },
    { id: 'user2', ...userNode, label: 'User 2' },
    { id: 'data1', ...dataNode, label: 'Data 1' },
    { id: 'data2', ...dataNode, label: 'Data 2' },
  ],
  edges: [
    { source: 'user1', target: 'data1', label: 'OWNS' },
    { source: 'data1', target: 'data2', label: 'OWNS' },
    { source: 'user2', target: 'data2', label: 'OWNS' },
  ]
});
</script>
