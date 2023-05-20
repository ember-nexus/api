# Scenario 2-01: Search Access Test

All users and groups which own elements or have access to them via HAS_SEARCH_ACCESS, should be able to search for them.
In the following example, all users and groups have search access to data nodes 1, 2 and 3, while data node 4 can only
be explored via related/children requests.

<div id="graph" class="graph-container" style="height:1000px"></div>


<script>
renderGraph(document.getElementById('graph'), {
  nodes: [
    { id: 'user1', ...userNode, label: 'User 1' },
    { id: 'user2', ...userNode, label: 'User 2' },
    { id: 'user3', ...userNode, label: 'User 3' },
    { id: 'user4', ...userNode, label: 'User 4' },
    { id: 'user5', ...userNode, label: 'User 5' },
    { id: 'user6', ...userNode, label: 'User 6' },
    { id: 'user7', ...userNode, label: 'User 7' },
    { id: 'group1', ...groupNode, label: 'Group 1' },
    { id: 'group2', ...groupNode, label: 'Group 2' },
    { id: 'group3', ...groupNode, label: 'Group 3' },
    { id: 'group4', ...groupNode, label: 'Group 4' },
    { id: 'data1', ...dataNode, label: 'Data 1' },
    { id: 'data2', ...dataNode, label: 'Data 2' },
    { id: 'data3', ...dataNode, label: 'Data 3' },
    { id: 'data4', ...dataNode, label: 'Data 4' },
  ],
  edges: [
    { source: 'user1', target: 'data1', label: 'OWNS 1' },
    { source: 'user3', target: 'data1', label: 'OWNS 2' },
    { source: 'group3', target: 'data1', label: 'OWNS 3' },
    { source: 'data1', target: 'data2', label: 'OWNS 4' },
    { source: 'user3', target: 'group1', label: 'IS_IN_GROUP 1' },
    { source: 'user4', target: 'group1', label: 'IS_IN_GROUP 2' },
    { source: 'group2', target: 'group1', label: 'IS_IN_GROUP 3' },
    { source: 'user5', target: 'group2', label: 'IS_IN_GROUP 4' },
    { source: 'user6', target: 'group3', label: 'IS_IN_GROUP 5' },
    { source: 'group4', target: 'group3', label: 'IS_IN_GROUP 6' },
    { source: 'user7', target: 'group4', label: 'IS_IN_GROUP 7' },
    { source: 'user2', target: 'data1', label: 'HAS_READ_ACCESS 1' },
    { source: 'group1', target: 'data1', label: 'HAS_READ_ACCESS 2' },
    { source: 'data2', target: 'data3', label: 'HAS_READ_ACCESS 3' },
    { source: 'data3', target: 'data4', label: 'HAS_READ_ACCESS 4' },
    { source: 'user2', target: 'data1', label: 'HAS_SEARCH_ACCESS 1' },
    { source: 'group1', target: 'data1', label: 'HAS_SEARCH_ACCESS 2' },
    { source: 'data2', target: 'data3', label: 'HAS_SEARCH_ACCESS 3' },
  ]
});
</script>
