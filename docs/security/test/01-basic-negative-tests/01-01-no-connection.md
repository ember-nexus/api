# Scenario 1-01: No Connection

Users who are not connected to nodes in any way, can not have access to them:

<div id="graph" class="graph-container" style="height:300px"></div>

| Test         | Token  | Action                    | Options             | Result | Idempotent | State of Test  |
|:-------------|:-------|:--------------------------|:--------------------|:-------|:-----------|:---------------|
| `1-01-01-01` | `User` | `🔵 GET /`                | -                   | ✔️ 200 | yes        | ✔️ implemented |
| `1-01-01-02` | `User` | `🔵 GET /<User>`          | -                   | ✔️ 200 | yes        | ✔️ implemented |
| `1-01-02-01` | `User` | `🔵 GET /<Data>`          | -                   | ❌ 404  | yes        | ✔️ implemented |
| `1-01-02-02` | `User` | `🔵 GET /<Data>/parents`  | -                   | ❌ 404  | yes        | ✔️ implemented |
| `1-01-02-03` | `User` | `🔵 GET /<Data>/children` | -                   | ❌ 404  | yes        | ✔️ implemented |
| `1-01-02-04` | `User` | `🔵 GET /<Data>/related`  | -                   | ❌ 404  | yes        | ✔️ implemented |
| `1-01-02-05` | `User` | `🟢 POST /<Data>`         | Valid request body. | ❌ 404  | yes        | ✔️ implemented |
| `1-01-02-06` | `User` | `🟠 PUT /<Data>`          | Valid request body. | ❌ 404  | yes        | ✔️ implemented |
| `1-01-02-07` | `User` | `🟠 PATCH /<Data>`        | Valid request body. | ❌ 404  | yes        | ✔️ implemented |
| `1-01-02-08` | `User` | `🔴 DELETE /<Data>`       | -                   | ❌ 404  | yes        | ✔️ implemented |
| `1-01-02-20` | `User` | `🔵 GET /<Data>/file`     | -                   | ❌ 404  | yes        | ✔️ implemented |
| `1-01-02-21` | `User` | `🟢 POST /<Data>/file`    | Valid request body. | ❌ 404  | yes        | ✔️ implemented |
| `1-01-02-22` | `User` | `🟠 PUT /<Data>/file`     | Valid request body. | ❌ 404  | yes        | ✔️ implemented |
| `1-01-02-23` | `User` | `🟠 PATCH /<Data>/file`   | Valid request body. | ❌ 404  | yes        | ✔️ implemented |
| `1-01-02-24` | `User` | `🔴 DELETE /<Data>/file`  | -                   | ❌ 404  | yes        | ✔️ implemented |

<script>
renderGraph(document.getElementById('graph'), {
  nodes: [
    { id: 'user', ...userNode },
    { id: 'data', ...dataNode },
  ],
  edges: []
}, 'TB');
</script>
