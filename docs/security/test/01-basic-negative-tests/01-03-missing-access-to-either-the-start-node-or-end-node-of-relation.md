# Scenario 1-03: Missing access to either the start or end node of relation

If a user has the required access to either the start or end node of a relationship but not the other, then he does not
have the access required to work with the relationship.

In the following example, the user can not access the `RELATIONSHIP`-relationship, as he does not have access to the
second data node.

<div id="graph" class="graph-container" style="height:400px"></div>

| Test         | Token  | Action                          | Options | Result | Idempotent | State of Test  |
|:-------------|:-------|:--------------------------------|:--------|:-------|:-----------|:---------------|
| `1-03-01-01` | `User` | `🔵 GET /`                      | -       | ✔️ 200 | yes        | ✔️ implemented |
| `1-03-01-02` | `User` | `🔵 GET /<User>`                | -       | ✔️ 200 | yes        | ✔️ implemented |
| `1-03-02-01` | `User` | `🔵 GET /<OWNS>`                | -       | ✔️ 200 | yes        | ✔️ implemented |
| `1-03-03-01` | `User` | `🔵 GET /<Data 1>`              | -       | ✔️ 200 | yes        | ✔️ implemented |
| `1-03-04-01` | `User` | `🔵 GET /<RELATION 1>`          | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-03-04-02` | `User` | `🔵 GET /<RELATION 1>/parents`  | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-03-04-03` | `User` | `🔵 GET /<RELATION 1>/children` | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-03-04-04` | `User` | `🔵 GET /<RELATION 1>/related`  | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-03-04-05` | `User` | `🟢 POST /<RELATION 1>`         | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-03-04-06` | `User` | `🟠 PUT /<RELATION 1>`          | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-03-04-07` | `User` | `🟠 PATCH /<RELATION 1>`        | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-03-04-08` | `User` | `🔴 DELETE /<RELATION 1>`       | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-03-04-20` | `User` | `🔵 GET /<RELATION 1>/file`     | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-03-04-21` | `User` | `🟢 POST /<RELATION 1>/file`    | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-03-04-22` | `User` | `🟠 PUT /<RELATION 1>/file`     | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-03-04-23` | `User` | `🟠 PATCH /<RELATION 1>/file`   | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-03-04-24` | `User` | `🔴 DELETE /<RELATION 1>/file`  | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-03-05-01` | `User` | `🔵 GET /<Data 2>`              | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-03-06-01` | `User` | `🔵 GET /<RELATION 2>`          | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-03-06-02` | `User` | `🔵 GET /<RELATION 2>/parents`  | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-03-06-03` | `User` | `🔵 GET /<RELATION 2>/children` | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-03-06-04` | `User` | `🔵 GET /<RELATION 2>/related`  | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-03-06-05` | `User` | `🟢 POST /<RELATION 2>`         | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-03-06-06` | `User` | `🟠 PUT /<RELATION 2>`          | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-03-06-07` | `User` | `🟠 PATCH /<RELATION 2>`        | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-03-06-08` | `User` | `🔴 DELETE /<RELATION 2>`       | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-03-06-20` | `User` | `🔵 GET /<RELATION 2>/file`     | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-03-06-21` | `User` | `🟢 POST /<RELATION 2>/file`    | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-03-06-22` | `User` | `🟠 PUT /<RELATION 2>/file`     | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-03-06-23` | `User` | `🟠 PATCH /<RELATION 2>/file`   | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-03-06-24` | `User` | `🔴 DELETE /<RELATION 2>/file`  | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-03-07-01` | `User` | `🔵 GET /<Data 3>`              | -       | ❌ 404  | yes        | ✔️ implemented |

<script>
renderGraph(document.getElementById('graph'), {
  nodes: [
    { id: 'user', ...userNode },
    { id: 'data1', ...dataNode, label: 'Data 1' },
    { id: 'data2', ...dataNode, label: 'Data 2' },
    { id: 'data3', ...dataNode, label: 'Data 3' },
  ],
  edges: [
    { source: 'user', target: 'data1', label: 'OWNS' },
    { source: 'data1', target: 'data2', label: 'RELATION 1' },
    { source: 'data3', target: 'data1', label: 'RELATION 2' },
  ]
});
</script>
