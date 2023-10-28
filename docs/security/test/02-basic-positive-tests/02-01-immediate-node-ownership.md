# Scenario 2-01: Immediate Node Ownership

Users which immediately own a node, have full access to it.

<div id="graph" class="graph-container" style="height:300px"></div>

| Test         | Token  | Action                    | Options             | Result | Idempotent | State of Test  |
|:-------------|:-------|:--------------------------|:--------------------|:-------|:-----------|:---------------|
| `2-01-01-01` | `User` | `🔵 GET /`                | -                   | ✔️ 200 | yes        | ✔️ implemented |
| `2-01-01-02` | `User` | `🔵 GET /<User>`          | -                   | ✔️ 200 | yes        | ✔️ implemented |
| `2-01-02-01` | `User` | `🔵 GET /<OWNS>`          | -                   | ✔️ 200 | yes        | ✔️ implemented |
| `2-01-03-01` | `User` | `🔵 GET /<Data>`          | -                   | ✔️ 200 | yes        | ✔️ implemented |
| `2-01-03-02` | `User` | `🔵 GET /<Data>/parents`  | -                   | ✔️ 200 | yes        | ✔️ implemented |
| `2-01-03-03` | `User` | `🔵 GET /<Data>/children` | -                   | ✔️ 200 | yes        | ✔️ implemented |
| `2-01-03-04` | `User` | `🔵 GET /<Data>/related`  | -                   | ✔️ 200 | yes        | ✔️ implemented |
| `2-01-03-05` | `User` | `🟢 POST /<Data>`         | Valid request body. | ✔️ 201 | no         | ✔️ implemented |
| `2-01-03-06` | `User` | `🟠 PUT /<Data>`          | Valid request body. | ✔️ 204 | no         | ✔️ implemented |
| `2-01-03-07` | `User` | `🟠 PATCH /<Data>`        | Valid request body. | ✔️ 204 | no         | ✔️ implemented |
| `2-01-03-08` | `User` | `🔴 DELETE /<Data>`       | -                   | ✔️ 204 | no         | ✔️ implemented |
| `2-01-03-20` | `User` | `🔵 GET /<Data>/file`     | -                   | ✔️ 200 | yes        | ❌ todo v0.2.0  |
| `2-01-03-21` | `User` | `🟢 POST /<Data>/file`    | Valid request body. | ✔️ 201 | no         | ❌ todo v0.2.0  |
| `2-01-03-22` | `User` | `🟠 PUT /<Data>/file`     | Valid request body. | ✔️ 204 | no         | ❌ todo v0.2.0  |
| `2-01-03-23` | `User` | `🟠 PATCH /<Data>/file`   | Valid request body. | ✔️ 204 | no         | ❌ todo v0.2.0  |
| `2-01-03-24` | `User` | `🔴 DELETE /<Data>/file`  | -                   | ✔️ ?   | no         | ❌ todo v0.2.0  |
| `2-01-03-30` | `User` | `🟣 COPY /<Data>`         | Valid request body. | ✔️ ?   | no         | ❌ todo v0.2.0  |
| `2-01-03-31` | `User` | `🟣 LOCK /<Data>`         | Valid request body. | ✔️ ?   | no         | ❌ todo v0.2.0  |
| `2-01-03-32` | `User` | `🟣 UNLOCK /<Data>`       | Valid request body. | ✔️ ?   | no         | ❌ todo v0.2.0  |
| `2-01-03-33` | `User` | `🟣 MKCOL /<Data>`        | Valid request body. | ✔️ ?   | no         | ❌ todo v0.2.0  |
| `2-01-03-34` | `User` | `🟣 MOVE /<Data>`         | Valid request body. | ✔️ ?   | no         | ❌ todo v0.2.0  |
| `2-01-03-35` | `User` | `🟣 PROPFIND /<Data>`     | Valid request body. | ✔️ ?   | yes?       | ❌ todo v0.2.0  |
| `2-01-03-36` | `User` | `🟣 PROPPATCH /<Data>`    | Valid request body. | ✔️ ?   | no         | ❌ todo v0.2.0  |

<script>
renderGraph(document.getElementById('graph'), {
  nodes: [
    { id: 'user', ...userNode },
    { id: 'data', ...dataNode },
  ],
  edges: [
    { source: 'user', target: 'data', label: 'OWNS' },
  ]
});
</script>
