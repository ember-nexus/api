# Scenario 1-03: Missing access to either the start or end node of relation

If a user has the required access to either the start or end node of a relationship but not the other, then he does not
have the access required to work with the relationship.

In the following example, the user can not access the `RELATIONSHIP`-relationship, as he does not have access to the
second data node.

<div id="graph" class="graph-container" style="height:400px"></div>

| Test         | Token  | Action                        | Options | Result | Idempotent | State of Test                                                 |
|:-------------|:-------|:------------------------------|:--------|:-------|:-----------|:--------------------------------------------------------------|
| `1-03-02-01` | `User` | `🔵 GET /<OWNS>`              | -       | ✔️ 200 | yes        | ✔️ implemented                                                |
| `1-03-02-02` | `User` | `🔵 GET /<OWNS>/parents`      | -       | ✔️ 200 | yes        | 🚧 todo                                                       |
| `1-03-02-03` | `User` | `🔵 GET /<OWNS>/children`     | -       | ✔️ 200 | yes        | 🚧 todo                                                       |
| `1-03-02-04` | `User` | `🔵 GET /<OWNS>/related`      | -       | ✔️ 200 | yes        | 🚧 todo                                                       |
| `1-03-02-05` | `User` | `🔵 GET /<OWNS>`              | -       | ✔️ 200 | yes        | 🚧 todo                                                       |
| `1-03-02-06` | `User` | `🟢 POST /<OWNS>`             | -       | ✔️ 204 | no         | 🚧 todo                                                       |
| `1-03-02-07` | `User` | `🟠 PUT /<OWNS>`              | -       | ✔️ 204 | no         | 🚧 todo                                                       |
| `1-03-02-08` | `User` | `🟠 PATCH /<OWNS>`            | -       | ✔️ 204 | no         | 🚧 todo                                                       |
| `1-03-02-09` | `User` | `🔴 DELETE /<OWNS>`           | -       | ✔️ 204 | no         | 🚧 todo                                                       |
| `1-03-02-20` | `User` | `🔵 GET /<OWNS>/file`         | -       | ✔️ 200 | yes        | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-02-21` | `User` | `🟢 POST /<OWNS>/file`        | -       | ✔️ 204 | no         | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-02-22` | `User` | `🟠 PUT /<OWNS>/file`         | -       | ✔️ 204 | no         | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-02-23` | `User` | `🟠 PATCH /<OWNS>/file`       | -       | ✔️ 204 | no         | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-02-24` | `User` | `🔴 DELETE /<OWNS>/file`      | -       | ✔️ 204 | no         | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-02-30` | `User` | `🟣 COPY /<OWNS>`             | -       | ✔️ 200 | no         | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-02-31` | `User` | `🟣 LOCK /<OWNS>`             | -       | ✔️ 200 | no         | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-02-32` | `User` | `🟣 UNLOCK /<OWNS>`           | -       | ✔️ 200 | no         | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-02-33` | `User` | `🟣 MKCOL /<OWNS>`            | -       | ✔️ 200 | no         | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-02-34` | `User` | `🟣 MOVE /<OWNS>`             | -       | ✔️ 200 | no         | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-02-35` | `User` | `🟣 PROPFIND /<OWNS>`         | -       | ✔️ 200 | yes        | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-02-36` | `User` | `🟣 PROPPATCH /<OWNS>`        | -       | ✔️ 200 | no         | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-03-01` | `User` | `🔵 GET /<Data 1>`            | -       | ✔️ 200 | yes        | ✔️ implemented                                                |
| `1-03-03-02` | `User` | `🔵 GET /<Data 1>/parents`    | -       | ✔️ 200 | yes        | 🚧 todo                                                       |
| `1-03-03-03` | `User` | `🔵 GET /<Data 1>/children`   | -       | ✔️ 200 | yes        | 🚧 todo                                                       |
| `1-03-03-04` | `User` | `🔵 GET /<Data 1>/related`    | -       | ✔️ 200 | yes        | 🚧 todo                                                       |
| `1-03-03-05` | `User` | `🔵 GET /<Data 1>`            | -       | ✔️ 200 | yes        | 🚧 todo                                                       |
| `1-03-03-06` | `User` | `🟢 POST /<Data 1>`           | -       | ✔️ 204 | no         | 🚧 todo                                                       |
| `1-03-03-07` | `User` | `🟠 PUT /<Data 1>`            | -       | ✔️ 204 | no         | 🚧 todo                                                       |
| `1-03-03-08` | `User` | `🟠 PATCH /<Data 1>`          | -       | ✔️ 204 | no         | 🚧 todo                                                       |
| `1-03-03-09` | `User` | `🔴 DELETE /<Data 1>`         | -       | ✔️ 204 | no         | 🚧 todo                                                       |
| `1-03-03-20` | `User` | `🔵 GET /<Data 1>/file`       | -       | ✔️ 200 | yes        | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-03-21` | `User` | `🟢 POST /<Data 1>/file`      | -       | ✔️ 204 | no         | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-03-22` | `User` | `🟠 PUT /<Data 1>/file`       | -       | ✔️ 204 | no         | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-03-23` | `User` | `🟠 PATCH /<Data 1>/file`     | -       | ✔️ 204 | no         | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-03-24` | `User` | `🔴 DELETE /<Data 1>/file`    | -       | ✔️ 204 | no         | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-03-30` | `User` | `🟣 COPY /<Data 1>`           | -       | ✔️ 200 | no         | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-03-31` | `User` | `🟣 LOCK /<Data 1>`           | -       | ✔️ 200 | no         | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-03-32` | `User` | `🟣 UNLOCK /<Data 1>`         | -       | ✔️ 200 | no         | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-03-33` | `User` | `🟣 MKCOL /<Data 1>`          | -       | ✔️ 200 | no         | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-03-34` | `User` | `🟣 MOVE /<Data 1>`           | -       | ✔️ 200 | no         | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-03-35` | `User` | `🟣 PROPFIND /<Data 1>`       | -       | ✔️ 200 | yes        | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-03-36` | `User` | `🟣 PROPPATCH /<Data 1>`      | -       | ✔️ 200 | no         | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-04-01` | `User` | `🔵 GET /<RELATION>`          | -       | ❌ 404  | yes        | ✔️ implemented                                                |
| `1-03-04-02` | `User` | `🔵 GET /<RELATION>/parents`  | -       | ❌ 404  | yes        | 🚧 todo                                                       |
| `1-03-04-03` | `User` | `🔵 GET /<RELATION>/children` | -       | ❌ 404  | yes        | 🚧 todo                                                       |
| `1-03-04-04` | `User` | `🔵 GET /<RELATION>/related`  | -       | ❌ 404  | yes        | 🚧 todo                                                       |
| `1-03-04-05` | `User` | `🔵 GET /<RELATION>`          | -       | ❌ 404  | yes        | 🚧 todo                                                       |
| `1-03-04-06` | `User` | `🟢 POST /<RELATION>`         | -       | ❌ 404  | yes        | 🚧 todo                                                       |
| `1-03-04-07` | `User` | `🟠 PUT /<RELATION>`          | -       | ❌ 404  | yes        | 🚧 todo                                                       |
| `1-03-04-08` | `User` | `🟠 PATCH /<RELATION>`        | -       | ❌ 404  | yes        | 🚧 todo                                                       |
| `1-03-04-09` | `User` | `🔴 DELETE /<RELATION>`       | -       | ❌ 404  | yes        | 🚧 todo                                                       |
| `1-03-04-20` | `User` | `🔵 GET /<RELATION>/file`     | -       | ❌ 404  | yes        | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-04-21` | `User` | `🟢 POST /<RELATION>/file`    | -       | ❌ 404  | yes        | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-04-22` | `User` | `🟠 PUT /<RELATION>/file`     | -       | ❌ 404  | yes        | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-04-23` | `User` | `🟠 PATCH /<RELATION>/file`   | -       | ❌ 404  | yes        | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-04-24` | `User` | `🔴 DELETE /<RELATION>/file`  | -       | ❌ 404  | yes        | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-04-30` | `User` | `🟣 COPY /<RELATION>`         | -       | ❌ 404  | yes        | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-04-31` | `User` | `🟣 LOCK /<RELATION>`         | -       | ❌ 404  | yes        | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-04-32` | `User` | `🟣 UNLOCK /<RELATION>`       | -       | ❌ 404  | yes        | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-04-33` | `User` | `🟣 MKCOL /<RELATION>`        | -       | ❌ 404  | yes        | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-04-34` | `User` | `🟣 MOVE /<RELATION>`         | -       | ❌ 404  | yes        | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-04-35` | `User` | `🟣 PROPFIND /<RELATION>`     | -       | ❌ 404  | yes        | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-04-36` | `User` | `🟣 PROPPATCH /<RELATION>`    | -       | ❌ 404  | yes        | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-05-01` | `User` | `🔵 GET /<Data 2>`            | -       | ❌ 404  | yes        | ✔️ implemented                                                |
| `1-03-05-02` | `User` | `🔵 GET /<Data 2>/parents`    | -       | ❌ 404  | yes        | 🚧 todo                                                       |
| `1-03-05-03` | `User` | `🔵 GET /<Data 2>/children`   | -       | ❌ 404  | yes        | 🚧 todo                                                       |
| `1-03-05-04` | `User` | `🔵 GET /<Data 2>/related`    | -       | ❌ 404  | yes        | 🚧 todo                                                       |
| `1-03-05-05` | `User` | `🔵 GET /<Data 2>`            | -       | ❌ 404  | yes        | 🚧 todo                                                       |
| `1-03-05-06` | `User` | `🟢 POST /<Data 2>`           | -       | ❌ 404  | yes        | 🚧 todo                                                       |
| `1-03-05-07` | `User` | `🟠 PUT /<Data 2>`            | -       | ❌ 404  | yes        | 🚧 todo                                                       |
| `1-03-05-08` | `User` | `🟠 PATCH /<Data 2>`          | -       | ❌ 404  | yes        | 🚧 todo                                                       |
| `1-03-05-09` | `User` | `🔴 DELETE /<Data 2>`         | -       | ❌ 404  | yes        | 🚧 todo                                                       |
| `1-03-05-20` | `User` | `🔵 GET /<Data 2>/file`       | -       | ❌ 404  | yes        | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-05-21` | `User` | `🟢 POST /<Data 2>/file`      | -       | ❌ 404  | yes        | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-05-22` | `User` | `🟠 PUT /<Data 2>/file`       | -       | ❌ 404  | yes        | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-05-23` | `User` | `🟠 PATCH /<Data 2>/file`     | -       | ❌ 404  | yes        | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-05-24` | `User` | `🔴 DELETE /<Data 2>/file`    | -       | ❌ 404  | yes        | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-05-30` | `User` | `🟣 COPY /<Data 2>`           | -       | ❌ 404  | yes        | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-05-31` | `User` | `🟣 LOCK /<Data 2>`           | -       | ❌ 404  | yes        | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-05-32` | `User` | `🟣 UNLOCK /<Data 2>`         | -       | ❌ 404  | yes        | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-05-33` | `User` | `🟣 MKCOL /<Data 2>`          | -       | ❌ 404  | yes        | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-05-34` | `User` | `🟣 MOVE /<Data 2>`           | -       | ❌ 404  | yes        | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-05-35` | `User` | `🟣 PROPFIND /<Data 2>`       | -       | ❌ 404  | yes        | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |
| `1-03-05-36` | `User` | `🟣 PROPPATCH /<Data 2>`      | -       | ❌ 404  | yes        | 🚧 todo [v0.2.0](https://github.com/ember-nexus/api/issues/7) |

<script>
renderGraph(document.getElementById('graph'), {
  nodes: [
    { id: 'user', ...userNode },
    { id: 'data1', ...dataNode, label: 'Data 1' },
    { id: 'data2', ...dataNode, label: 'Data 2' },
  ],
  edges: [
    { source: 'user', target: 'data1', label: 'OWNS' },
    { source: 'data1', target: 'data2', label: 'RELATION' },
  ]
});
</script>
