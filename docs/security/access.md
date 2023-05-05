# Access

Users can be granted selective access to nodes.  
In the following example, User A does own both data nodes, and User B can read both:

<div id="graph-container-1" class="graph-container"></div>

There are different types of access relations:

- `HAS_READ_ACCESS`: Grants read privileges to nodes.
- `HAS_SEARCH_ACCESS`: Grants search privileges to nodes.  
  Search access will not grant read access automatically, you have to grant both individually. In most cases you should
  grant both, except if you want to exclude node trees from the search, e.g. when they contain sensitive data.
- `HAS_CREATE_ACCESS`: Grants the right to create new child nodes.
- `HAS_UPDATE_ACCESS`: Grants the right to update existing nodes.
- `HAS_DELETE_ACCESS`: Grants the right to delete existing nodes.

!> **Note**: All access rules are recursive, i.e. child nodes will inherit privileges from their parents.

## Limiting Access to Node Types

The `HAS_X_ACCESS` relationships can be limited to individual node types by setting the `onLabel`-property of the
relationship to the node label. You can use multiple `HAS_X_ACCESS`-relationships if you want to grant access to
multiple node types.

In the following example, User A owns all nodes, while User B can only read the Doc 2 node:

<div id="graph-container-2" class="graph-container" style="height:700px"></div>

## Limiting Access to Parent Node Types

In some situations you would want to restrict access based on the parent's type. For example, in a blog you might want
users to create comments to blog posts, but not to authors or images.

To restrict `HAS_X_ACCESS` relationships in this way, you have to set the `onParentLabel`-property.

In the following example, the Admin user owns all nodes, while the Anom user (anonymous user) can only:

- Read blog posts and their child elements.
- Create comments on posts themselves.

<div id="graph-container-3" class="graph-container" style="height:700px"></div>

## Limiting Access to Workflow Stages

You can define workflows with multiple stages, which each have different effects on the security system.

This can be achieved by setting the property `withState` of `HAS_X_ACCESS` relations to the uuid of the state, on which
the access should be granted.

!> Note: Internally access can only be granted, not removed. Furthermore, the existence of the state is checked for each
of the element's parents. So try to set the state as late as possible.

In the following example, the Admin user still owns everything, while the Anom user can only read Post 1, because it is
the only one which has the Active state set:

<div id="graph-container-4" class="graph-container"></div>

## Limiting Access to Owners

In some situations users should be able to create data elements, edit and delete their own, but not be able to modify
data elements of other users.

To achieve this, you have to set the `onCreatedByUser`-property of the `HAS_X_ACCESS` relationship to `true`.

<div id="graph-container-5" class="graph-container" style="height:700px"></div>

## Internal Cypher Query

Internally the access permissions are checked with the following Cypher query:

```cypher
MATCH (user:User {id: "<uuid>"})
MATCH (element {id: "<uuid>"})
MATCH (user)-[:IS_IN_GROUP*0..]->()-[r:OWNS|HAS_X_ACCESS]->(element)
WHERE
  type(r) = "OWNS"
  OR
  (
    type(r) = "HAS_X_ACCESS"
    AND
    (
      r.onLabel IS NOT NULL
      OR
      r.onLabel IN labels(element)
    )
    AND
    (
      r.onParentLabel IS NOT NULL
      OR
      r.onParentLabel IN labels(element)
    )
    AND
    (
      r.onState IS NOT NULL
      OR
      (element)<-[:OWNS*0..]-()-[:HAS_STATE]->(:State {id: r.onState})
    )
    AND
    (
      r.onCreatedByUser IS NOT NULL
      OR
      (element)<-[:REATED_BY*]-(user)
    )
  )
RETURN user, element;
```

`HAS_X_ACCESS` is replaced by the requested action, e.g. `HAS_READ_ACCESS`.

<script>
renderGraph(document.getElementById('graph-container-1'), {
  nodes: [
    { id: 'user1', ...userNode, label: 'User A' },
    { id: 'user2', ...userNode, label: 'User B' },
    { id: 'data1', ...dataNode },
    { id: 'data2', ...dataNode }
  ],
  edges: [
    { source: 'user1', target: 'data1', label: 'OWNS' },
    { source: 'user2', target: 'data1', label: 'HAS_READ_ACCESS' },
    { source: 'data1', target: 'data2', label: 'OWNS' },
  ]
});
renderGraph(document.getElementById('graph-container-2'), {
  nodes: [
    { id: 'user1', ...userNode, label: 'User A' },
    { id: 'user2', ...userNode, label: 'User B' },
    { id: 'data1', ...dataNode, label: 'Data 1' },
    { id: 'document1', ...documentNode, label: 'Doc 1' },
    { id: 'data2', ...dataNode, label: 'Data 2' },
    { id: 'data3', ...dataNode, label: 'Data 3' },
    { id: 'data4', ...dataNode, label: 'Data 4' },
    { id: 'document2', ...documentNode, label: 'Doc 2' },
  ],
  edges: [
    { source: 'user1', target: 'data1', label: 'OWNS' },
    { source: 'user1', target: 'data2', label: 'OWNS' },
    { source: 'user1', target: 'document1', label: 'OWNS' },
    { source: 'data2', target: 'data3', label: 'OWNS' },
    { source: 'data2', target: 'data4', label: 'OWNS' },
    { source: 'data2', target: 'document2', label: 'OWNS' },
    { source: 'user2', target: 'data2', label: "HAS_READ_ACCESS\nonLabel: Doc" }
  ]
}, 'TB');
renderGraph(document.getElementById('graph-container-3'), {
  nodes: [
    { id: 'admin', ...userNode, label: 'Admin' },
    { id: 'anom', ...userNode, label: 'Anom' },
    { id: 'blog', ...dataNode, label: 'Blog' },
    { id: 'post1', ...dataNode, label: 'Post 1' },
    { id: 'post2', ...dataNode, label: 'Post 2' },
    { id: 'comment1', ...commentNode, label: 'Comment 1' },
    { id: 'comment2', ...commentNode, label: 'Comment 2' },
    { id: 'comment3', ...commentNode, label: 'Comment 3' },
  ],
  edges: [
    { source: 'admin', target: 'blog', label: 'OWNS' },
    { source: 'blog', target: 'post1', label: 'OWNS' },
    { source: 'blog', target: 'post2', label: 'OWNS' },
    { source: 'post1', target: 'comment1', label: 'OWNS' },
    { source: 'post1', target: 'comment2', label: 'OWNS' },
    { source: 'post2', target: 'comment3', label: 'OWNS' },
    { source: 'anom', target: 'blog', label: 'HAS_READ_ACCESS' },
    { source: 'anom', target: 'blog', label: "HAS_CREATE_ACCESS\nonParentLabel: Post" },
  ]
});
renderGraph(document.getElementById('graph-container-4'), {
  nodes: [
    { id: 'admin', ...userNode, label: 'Admin' },
    { id: 'anom', ...userNode, label: 'Anom' },
    { id: 'blog', ...dataNode, label: 'Blog' },
    { id: 'post1', ...dataNode, label: 'Post 1' },
    { id: 'post2', ...dataNode, label: 'Post 2' },
    { id: 'state1', ...workflowStateNode, label: 'Active' },
  ],
  edges: [
    { source: 'admin', target: 'blog', label: 'OWNS' },
    { source: 'blog', target: 'post1', label: 'OWNS' },
    { source: 'blog', target: 'post2', label: 'OWNS' },
    { source: 'anom', target: 'blog', label: "HAS_READ_ACCESS\nonState: <ActiveUUID>" },
    { source: 'post1', target: 'state1', label: 'HAS_STATE' },
  ]
});
renderGraph(document.getElementById('graph-container-5'), {
  nodes: [
    { id: 'user1', ...userNode, label: 'User A' },
    { id: 'user2', ...userNode, label: 'User B' },
    { id: 'group', ...groupNode, label: "Public\nGroup" },
    { id: 'blog', ...dataNode, label: 'Blog' },
    { id: 'post', ...dataNode, label: 'Post' },
    { id: 'comment1', ...commentNode, label: 'Comment 1' },
    { id: 'comment2', ...commentNode, label: 'Comment 2' }
  ],
  edges: [
    { source: 'blog', target: 'post', label: 'OWNS' },
    { source: 'post', target: 'comment1', label: 'OWNS' },
    { source: 'post', target: 'comment2', label: 'OWNS' },
    { source: 'user1', target: 'comment1', label: 'CREATED' },
    { source: 'user2', target: 'comment2', label: 'CREATED' },
    { source: 'user1', target: 'group', label: 'IS_IN_GROUP' },
    { source: 'user2', target: 'group', label: 'IS_IN_GROUP' },
    { source: 'group', target: 'blog', label: "HAS_READ_ACCESS\n\nHAS_CREATE_ACCESS\nonLabel: Post\n"+
      "onType:Comment\n\nHAS_UPDATE_ACCESS\nonCreatedByUser: true\n\nHAS_DELETE_ACCESS\nonCreatedByUser: true" },
  ]
});
</script>
