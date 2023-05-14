# Scenario 99-01: IS_IN_GROUP after OWNS has no effect

If an `IS_IN_GROUP` relationship appears after at least one `OWNS` relationship, then the `IS_IN_GROUP` relationship
must be ignored / will be handled like any non security related relationship.

In the following example, the user does not have any access to group 2 or data 2, as there are no `OWNS`-paths between
the user and those nodes.

<div id="graph" class="graph-container" style="height:400px"></div>

<script>
renderGraph(document.getElementById('graph'), {
  nodes: [
    { id: 'user', ...userNode },
    { id: 'group1', ...groupNode, label: 'Group 1' },
    { id: 'group2', ...groupNode, label: 'Group 2' },
    { id: 'data1', ...dataNode, label: 'Data 1' },
    { id: 'data2', ...dataNode, label: 'Data 2' },
  ],
  edges: [
    { source: 'user', target: 'group1', label: 'IS_IN_GROUP' },
    { source: 'group1', target: 'data1', label: 'OWNS' },
    { source: 'data1', target: 'group2', label: 'IS_IN_GROUP' },
    { source: 'group2', target: 'data2', label: 'OWNS' }
  ]
});
</script>
