# Scenario 4-04: Path Relation Ownership

Users which are in a chain of groups, where the last group is connected to both the start and end node of a relationship
through multiple chained `OWNS` relations have full access to the relationship.

In the example, the user has full access to the relationship with the type `RELATIONSHIP`.

<div id="graph" class="graph-container" style="height:400px"></div>

<script>
renderGraph(document.getElementById('graph'), {
  nodes: [
    { id: 'user', ...userNode },
    { id: 'group1', ...groupNode, label: 'Group 1' },
    { id: 'group2', ...groupNode, label: 'Group 2' },
    { id: 'group3', ...groupNode, label: 'Group 3' },
    { id: 'data1', ...dataNode, label: 'Data 1' },
    { id: 'data2', ...dataNode, label: 'Data 2' },
    { id: 'data3', ...dataNode, label: 'Data 3' },
    { id: 'data4', ...dataNode, label: 'Data 4' },
    { id: 'data5', ...dataNode, label: 'Data 5' },
    { id: 'data6', ...dataNode, label: 'Data 6' },
  ],
  edges: [
    { source: 'user', target: 'group1', label: 'IS_IN_GROUP' },
    { source: 'group1', target: 'group2', label: 'IS_IN_GROUP' },
    { source: 'group2', target: 'group3', label: 'IS_IN_GROUP' },
    { source: 'group3', target: 'data1', label: 'OWNS' },
    { source: 'data1', target: 'data2', label: 'OWNS' },
    { source: 'data2', target: 'data3', label: 'OWNS' },
    { source: 'group3', target: 'data4', label: 'OWNS' },
    { source: 'data4', target: 'data5', label: 'OWNS' },
    { source: 'data5', target: 'data6', label: 'OWNS' },
    { source: 'data3', target: 'data6', label: 'RELATIONSHIP' },
  ]
});
</script>
