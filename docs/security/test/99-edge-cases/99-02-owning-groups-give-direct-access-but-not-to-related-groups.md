# Scenario 99-02: Owning groups give direct access but not to related groups

If users own groups directly or through other groups, then they also own the elements the group owns.  
**However, they do not inherit access to groups connected via `IS_IN_GROUP` relationships.**

In the following example, the user has owning access to the data nodes 1 and 2, as there is a direct chain of
`OWNS`-relations. However, the user does not have access to data node 3.

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
  ],
  edges: [
    { source: 'user', target: 'group1', label: 'IS_IN_GROUP' },
    { source: 'group1', target: 'group2', label: 'OWNS' },
    { source: 'group2', target: 'group3', label: 'IS_IN_GROUP' },
    { source: 'group1', target: 'data1', label: 'OWNS' },
    { source: 'group2', target: 'data2', label: 'OWNS' },
    { source: 'group3', target: 'data3', label: 'OWNS' }
  ]
});
</script>
