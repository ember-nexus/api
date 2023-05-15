# Scenario 1-02: No Relevant Connection

Users which are connected to nodes with relationships, which are not relevant for the security system, do not have
access. Relevant relations include `OWNS`, `HAS_X_ACCESS` and `CREATED`.

<div id="graph" class="graph-container" style="height:300px"></div>

| Test         | Token  | Action                        | Options | Result | Idempotent | State of Test  |
|:-------------|:-------|:------------------------------|:--------|:-------|:-----------|:---------------|
| `1-02-01-01` | `User` | `🔵 GET /`                    | -       | ✔️ 200 | yes        | ✔️ implemented |
| `1-02-01-02` | `User` | `🔵 GET /<User>`              | -       | ✔️ 200 | yes        | ✔️ implemented |
| `1-02-02-01` | `User` | `🔵 GET /<RELATION>`          | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-02-02-02` | `User` | `🔵 GET /<RELATION>/parents`  | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-02-02-03` | `User` | `🔵 GET /<RELATION>/children` | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-02-02-04` | `User` | `🔵 GET /<RELATION>/related`  | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-02-02-05` | `User` | `🟢 POST /<RELATION>`         | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-02-02-06` | `User` | `🟠 PUT /<RELATION>`          | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-02-02-07` | `User` | `🟠 PATCH /<RELATION>`        | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-02-02-08` | `User` | `🔴 DELETE /<RELATION>`       | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-02-02-20` | `User` | `🔵 GET /<RELATION>/file`     | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-02-02-21` | `User` | `🟢 POST /<RELATION>/file`    | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-02-02-22` | `User` | `🟠 PUT /<RELATION>/file`     | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-02-02-23` | `User` | `🟠 PATCH /<RELATION>/file`   | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-02-02-24` | `User` | `🔴 DELETE /<RELATION>/file`  | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-02-02-30` | `User` | `🟣 COPY /<RELATION>`         | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-02-02-31` | `User` | `🟣 LOCK /<RELATION>`         | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-02-02-32` | `User` | `🟣 UNLOCK /<RELATION>`       | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-02-02-33` | `User` | `🟣 MKCOL /<RELATION>`        | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-02-02-34` | `User` | `🟣 MOVE /<RELATION>`         | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-02-02-35` | `User` | `🟣 PROPFIND /<RELATION>`     | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-02-02-36` | `User` | `🟣 PROPPATCH /<RELATION>`    | -       | ❌ 404  | yes        | ✔️ implemented |
| `1-02-03-01` | `User` | `🔵 GET /<Data>`              | -       | ❌ 404  | yes        | ✔️ implemented |

<script>
renderGraph(document.getElementById('graph'), {
  nodes: [
    { id: 'user', ...userNode },
    { id: 'data', ...dataNode },
  ],
  edges: [
    { source: 'user', target: 'data', label: 'RELATION' },
  ]
});
</script>
