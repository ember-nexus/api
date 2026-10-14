# Ownership

Users who own elements have full access to those elements.  
In the following example, the User can execute read, search, create, update and delete actions against the Data node:

<div id="graph-container-1" class="graph-container" style="height:300px"></div>

Ownership is recursive.  
The User has full access to both Data nodes:

<div id="graph-container-2" class="graph-container" style="height:300px"></div>

Groups can own elements too.  
All users and groups which are part of the group gain full access:

<div id="graph-container-3" class="graph-container"></div>

Internally the ownership is checked with the following Cypher query:

```cypher
MATCH (user:User {id: "<uuid>"})
MATCH (element {id: "<uuid>"})
MATCH (user)-[:IS_IN_GROUP*0..]->()-[:OWNS]->(element)
RETURN user, element;
```

<script>
renderGraph(document.getElementById('graph-container-1'), {
  nodes: [
    { id: 'user1', ...userNode },
    { id: 'data1', ...dataNode },
  ],
  edges: [
    { source: 'user1', target: 'data1', label: 'OWNS' },
  ]
});
renderGraph(document.getElementById('graph-container-2'), {
  nodes: [
    { id: 'user1', ...userNode },
    { id: 'data1', ...dataNode },
    { id: 'data2', ...dataNode }
  ],
  edges: [
    { source: 'user1', target: 'data1', label: 'OWNS' },
    { source: 'data1', target: 'data2', label: 'OWNS' }
  ]
});
renderGraph(document.getElementById('graph-container-3'), {
  nodes: [
    { id: 'user1', ...userNode },
    { id: 'user2', ...userNode },
    { id: 'group1', ...groupNode },
    { id: 'group2', ...groupNode },
    { id: 'data1', ...dataNode },
    { id: 'data2', ...dataNode },
  ],
  edges: [
    { source: 'user1', target: 'group1', label: 'IS_IN_GROUP' },
    { source: 'group1', target: 'data1', label: 'OWNS' },
    { source: 'user2', target: 'group2', label: 'IS_IN_GROUP' },
    { source: 'group2', target: 'group1', label: 'IS_IN_GROUP' },
    { source: 'data1', target: 'data2', label: 'OWNS' }
  ]
});
</script>
