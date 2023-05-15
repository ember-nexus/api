# Scenario 1-04: OWNS-relations are directional

`OWNS`-relationships are directional, i.e. users who have access to an owned element do not automatically have access to
its parents.

In the following example, user 1 has access to both data nodes, while user 2 can only access the second data node. The
`OWNS`-relationship between data 1 and data 2 is directional, i.e. can only grant the owners of data 1 access (user 1),
while the relationship is ignored for owners of data 2 (user 2):

<div id="graph" class="graph-container" style="height:400px"></div>

| Test         | Token    | Action             | Options | Result | Idempotent | State of Test  |
|:-------------|:---------|:-------------------|:--------|:-------|:-----------|:---------------|
| `1-04-01-01` | `User 1` | `🔵 GET /`         | -       | ✔️ 200 | yes        | ✔️ implemented |
| `1-04-01-02` | `User 1` | `🔵 GET /<User 1>` | -       | ✔️ 200 | yes        | ✔️ implemented |
| `1-04-02-01` | `User 1` | `🔵 GET /<OWNS 1>` | -       | ✔️ 200 | yes        | ✔️ implemented |
| `1-04-03-01` | `User 1` | `🔵 GET /<Data 1>` | -       | ✔️ 200 | yes        | ✔️ implemented |
| `1-04-04-01` | `User 1` | `🔵 GET /<OWNS 2>` | -       | ✔️ 200 | yes        | ✔️ implemented |
| `1-04-05-01` | `User 1` | `🔵 GET /<Data 2>` | -       | ✔️ 200 | yes        | ✔️ implemented |
| `1-04-06-01` | `User 1` | `🔵 GET /<OWNS 3>` | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-04-07-01` | `User 1` | `🔵 GET /<User 2>` | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-04-01-03` | `User 2` | `🔵 GET /`         | -       | ✔️ 200 | yes        | ✔️ implemented |
| `1-04-01-04` | `User 2` | `🔵 GET /<User 1>` | -       | ❌ 404 | yes        | ✔️ implemented |
| `1-04-02-02` | `User 2` | `🔵 GET /<OWNS 1>` | -       | ❌ 404 | yes        | ✔️ implemented |
| `1-04-03-02` | `User 2` | `🔵 GET /<Data 1>` | -       | ❌ 404 | yes        | ✔️ implemented |
| `1-04-04-02` | `User 2` | `🔵 GET /<OWNS 2>` | -       | ❌ 404 | yes        | ✔️ implemented |
| `1-04-05-02` | `User 2` | `🔵 GET /<Data 2>` | -       | ✔️ 200 | yes        | ✔️ implemented |
| `1-04-06-02` | `User 2` | `🔵 GET /<OWNS 3>` | -       | ✔️ 200 | yes        | ✔️ implemented |
| `1-04-07-02` | `User 2` | `🔵 GET /<User 2>` | -       | ✔️ 200  | yes        | ✔️ implemented |

<script>
renderGraph(document.getElementById('graph'), {
  nodes: [
    { id: 'user1', ...userNode, label: 'User 1' },
    { id: 'user2', ...userNode, label: 'User 2' },
    { id: 'data1', ...dataNode, label: 'Data 1' },
    { id: 'data2', ...dataNode, label: 'Data 2' },
  ],
  edges: [
    { source: 'user1', target: 'data1', label: 'OWNS 1' },
    { source: 'data1', target: 'data2', label: 'OWNS 2' },
    { source: 'user2', target: 'data2', label: 'OWNS 3' },
  ]
});
</script>
