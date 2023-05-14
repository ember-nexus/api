# Scenario 2-02: Immediate Relation Ownership

Users which immediately own both the start and end node of a relationship, have full access to the relationship.

In the example, the user has full access to the relationship with the type `RELATIONSHIP`.

<div id="graph" class="graph-container" style="height:400px"></div>

<script>
renderGraph(document.getElementById('graph'), {
  nodes: [
    { id: 'user', ...userNode },
    { id: 'data1', ...dataNode, label: 'Data 1' },
    { id: 'data2', ...dataNode, label: 'Data 2' },
  ],
  edges: [
    { source: 'user', target: 'data1', label: 'OWNS' },
    { source: 'user', target: 'data2', label: 'OWNS' },
    { source: 'data1', target: 'data2', label: 'RELATIONSHIP' },
  ]
});
</script>
