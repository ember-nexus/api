# Scenario 6-01: Limited Read Access

If a user is connected via `OWNS`- and `HAS_READ_ACCESS`-relations, then the user has read access to the element and
their children:

<div id="graph" class="graph-container" style="height:700px"></div>

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
    { id: 'data7', ...dataNode, label: 'Data 7' },
    { id: 'data8', ...dataNode, label: 'Data 8' },
    { id: 'data9', ...dataNode, label: 'Data 9' },
  ],
  edges: [
    { source: 'user', target: 'data1', label: 'HAS_READ_ACCESS' },
    { source: 'data1', target: 'data2', label: 'OWNS' },
    { source: 'data1', target: 'data3', label: 'OWNS' },
    { source: 'data2', target: 'data3', label: 'RELATIONSHIP' },
    { source: 'user', target: 'group1', label: 'IS_IN_GROUP' },
    { source: 'group1', target: 'data4', label: 'HAS_READ_ACCESS' },
    { source: 'data4', target: 'data5', label: 'OWNS' },
    { source: 'data4', target: 'data6', label: 'OWNS' },
    { source: 'data5', target: 'data6', label: 'RELATIONSHIP' },
    { source: 'user', target: 'group2', label: 'IS_IN_GROUP' },
    { source: 'group2', target: 'group3', label: 'IS_IN_GROUP' },
    { source: 'group3', target: 'data7', label: 'HAS_READ_ACCESS' },
    { source: 'data7', target: 'data8', label: 'OWNS' },
    { source: 'data7', target: 'data9', label: 'OWNS' },
    { source: 'data8', target: 'data9', label: 'RELATIONSHIP' }
  ]
});
</script>
