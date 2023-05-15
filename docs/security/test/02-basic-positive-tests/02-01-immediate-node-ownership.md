# Scenario 2-01: Immediate Node Ownership

Users which immediately own a node, have full access to it.

<div id="graph" class="graph-container" style="height:300px"></div>

| Test         | Token  | Action                    | Options             | Result | Idempotent | State of Test |
|:-------------|:-------|:--------------------------|:--------------------|:-------|:-----------|:--------------|
| `2-01-01-01` | `User` | `🔵 GET /`                | -                   | ✔️ 200 | yes        | ❌ todo        |
| `2-01-01-02` | `User` | `🔵 GET /<User>`          | -                   | ✔️ 200 | yes        | ❌ todo        |
| `2-01-02-01` | `User` | `🔵 GET /<Data>`          | -                   | ✔️ 200 | yes        | ❌ todo        |
| `2-01-02-02` | `User` | `🔵 GET /<Data>/parents`  | -                   | ✔️ 200 | yes        | ❌ todo        |
| `2-01-02-03` | `User` | `🔵 GET /<Data>/children` | -                   | ✔️ 200 | yes        | ❌ todo        |
| `2-01-02-04` | `User` | `🔵 GET /<Data>/related`  | -                   | ✔️ 200 | yes        | ❌ todo        |
| `2-01-02-05` | `User` | `🟢 POST /<Data>`         | Valid request body. | ✔️ 201 | no         | ❌ todo        |
| `2-01-02-06` | `User` | `🟠 PUT /<Data>`          | Valid request body. | ✔️ 204 | no         | ❌ todo        |
| `2-01-02-07` | `User` | `🟠 PATCH /<Data>`        | Valid request body. | ✔️ 204 | no         | ❌ todo        |
| `2-01-02-08` | `User` | `🔴 DELETE /<Data>`       | -                   | ✔️ ?   | no         | ❌ todo        |
| `2-01-02-20` | `User` | `🔵 GET /<Data>/file`     | -                   | ✔️ 200 | yes        | ❌ todo v0.2.0 |
| `2-01-02-21` | `User` | `🟢 POST /<Data>/file`    | Valid request body. | ✔️ 201 | no         | ❌ todo v0.2.0 |
| `2-01-02-22` | `User` | `🟠 PUT /<Data>/file`     | Valid request body. | ✔️ 204 | no         | ❌ todo v0.2.0 |
| `2-01-02-23` | `User` | `🟠 PATCH /<Data>/file`   | Valid request body. | ✔️ 204 | no         | ❌ todo v0.2.0 |
| `2-01-02-24` | `User` | `🔴 DELETE /<Data>/file`  | -                   | ✔️ ?   | no         | ❌ todo v0.2.0 |
| `2-01-02-30` | `User` | `🟣 COPY /<Data>`         | Valid request body. | ✔️ ?   | no         | ❌ todo v0.2.0 |
| `2-01-02-31` | `User` | `🟣 LOCK /<Data>`         | Valid request body. | ✔️ ?   | no         | ❌ todo v0.2.0 |
| `2-01-02-32` | `User` | `🟣 UNLOCK /<Data>`       | Valid request body. | ✔️ ?   | no         | ❌ todo v0.2.0 |
| `2-01-02-33` | `User` | `🟣 MKCOL /<Data>`        | Valid request body. | ✔️ ?   | no         | ❌ todo v0.2.0 |
| `2-01-02-34` | `User` | `🟣 MOVE /<Data>`         | Valid request body. | ✔️ ?   | no         | ❌ todo v0.2.0 |
| `2-01-02-35` | `User` | `🟣 PROPFIND /<Data>`     | Valid request body. | ✔️ ?   | yes?       | ❌ todo v0.2.0 |
| `2-01-02-36` | `User` | `🟣 PROPPATCH /<Data>`    | Valid request body. | ✔️ ?   | no         | ❌ todo v0.2.0 |

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
