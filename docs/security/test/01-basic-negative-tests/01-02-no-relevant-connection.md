# Scenario 1-02: No Relevant Connection

Users which are connected to nodes with relationships, which are not relevant for the security system, do not have
access. Relevant relations include `OWNS`, `HAS_X_ACCESS` and `CREATED`.

<div id="graph" class="graph-container" style="height:300px"></div>

| Test         | Token  | Action                        | Options | Result | State of Test                                                 |
|:-------------|:-------|:------------------------------|:--------|:-------|:--------------------------------------------------------------|
| `1-02-02-01` | `User` | `🔵 GET /<RELATION>`          | -       | ❌ 404  | ✔️ implemented                                                |
| `1-02-02-02` | `User` | `🔵 GET /<RELATION>/parents`  | -       | ❌ 404  | 🚧 todo                                                       |
| `1-02-02-03` | `User` | `🔵 GET /<RELATION>/children` | -       | ❌ 404  | 🚧 todo                                                       |
| `1-02-02-04` | `User` | `🔵 GET /<RELATION>/related`  | -       | ❌ 404  | 🚧 todo                                                       |
| `1-02-02-05` | `User` | `🔵 GET /<RELATION>`          | -       | ❌ 404  | 🚧 todo                                                       |
| `1-02-02-06` | `User` | `🟢 POST /<RELATION>`         | -       | ❌ 404  | 🚧 todo                                                       |
| `1-02-02-07` | `User` | `🟠 PUT /<RELATION>`          | -       | ❌ 404  | 🚧 todo                                                       |
| `1-02-02-08` | `User` | `🟠 PATCH /<RELATION>`        | -       | ❌ 404  | 🚧 todo                                                       |
| `1-02-02-09` | `User` | `🔴 DELETE /<RELATION>`       | -       | ❌ 404  | 🚧 todo                                                       |
| `1-02-02-20` | `User` | `🔵 GET /<RELATION>/file`     | -       | ❌ 404  | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-02-02-21` | `User` | `🟢 POST /<RELATION>/file`    | -       | ❌ 404  | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-02-02-22` | `User` | `🟠 PUT /<RELATION>/file`     | -       | ❌ 404  | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-02-02-23` | `User` | `🟠 PATCH /<RELATION>/file`   | -       | ❌ 404  | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-02-02-24` | `User` | `🔴 DELETE /<RELATION>/file`  | -       | ❌ 404  | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-02-02-30` | `User` | `🟣 COPY /<RELATION>`         | -       | ❌ 404  | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-02-02-31` | `User` | `🟣 LOCK /<RELATION>`         | -       | ❌ 404  | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-02-02-32` | `User` | `🟣 UNLOCK /<RELATION>`       | -       | ❌ 404  | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-02-02-33` | `User` | `🟣 MKCOL /<RELATION>`        | -       | ❌ 404  | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-02-02-34` | `User` | `🟣 MOVE /<RELATION>`         | -       | ❌ 404  | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-02-02-35` | `User` | `🟣 PROPFIND /<RELATION>`     | -       | ❌ 404  | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-02-02-36` | `User` | `🟣 PROPPATCH /<RELATION>`    | -       | ❌ 404  | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-02-03-01` | `User` | `🔵 GET /<Data>`              | -       | ❌ 404  | ✔️ implemented                                                |
| `1-02-03-02` | `User` | `🔵 GET /<Data>/parents`      | -       | ❌ 404  | 🚧 todo                                                       |
| `1-02-03-03` | `User` | `🔵 GET /<Data>/children`     | -       | ❌ 404  | 🚧 todo                                                       |
| `1-02-03-04` | `User` | `🔵 GET /<Data>/related`      | -       | ❌ 404  | 🚧 todo                                                       |
| `1-02-03-05` | `User` | `🔵 GET /<Data>`              | -       | ❌ 404  | 🚧 todo                                                       |
| `1-02-03-06` | `User` | `🟢 POST /<Data>`             | -       | ❌ 404  | 🚧 todo                                                       |
| `1-02-03-07` | `User` | `🟠 PUT /<Data>`              | -       | ❌ 404  | 🚧 todo                                                       |
| `1-02-03-08` | `User` | `🟠 PATCH /<Data>`            | -       | ❌ 404  | 🚧 todo                                                       |
| `1-02-03-09` | `User` | `🔴 DELETE /<Data>`           | -       | ❌ 404  | 🚧 todo                                                       |
| `1-02-03-20` | `User` | `🔵 GET /<Data>/file`         | -       | ❌ 404  | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-02-03-21` | `User` | `🟢 POST /<Data>/file`        | -       | ❌ 404  | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-02-03-22` | `User` | `🟠 PUT /<Data>/file`         | -       | ❌ 404  | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-02-03-23` | `User` | `🟠 PATCH /<Data>/file`       | -       | ❌ 404  | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-02-03-24` | `User` | `🔴 DELETE /<Data>/file`      | -       | ❌ 404  | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-02-03-30` | `User` | `🟣 COPY /<Data>`             | -       | ❌ 404  | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-02-03-31` | `User` | `🟣 LOCK /<Data>`             | -       | ❌ 404  | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-02-03-32` | `User` | `🟣 UNLOCK /<Data>`           | -       | ❌ 404  | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-02-03-33` | `User` | `🟣 MKCOL /<Data>`            | -       | ❌ 404  | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-02-03-34` | `User` | `🟣 MOVE /<Data>`             | -       | ❌ 404  | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-02-03-35` | `User` | `🟣 PROPFIND /<Data>`         | -       | ❌ 404  | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-02-03-36` | `User` | `🟣 PROPPATCH /<Data>`        | -       | ❌ 404  | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |

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
