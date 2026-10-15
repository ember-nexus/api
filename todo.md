# Docs / swagger generation — known gaps

Tracking remaining spots where `docs/src/data/swagger/**` is still hand-authored (not sourced
from `tests/recordings/`), or where a fragment/recording is simply missing. See
`docs/scripts/generate-swagger.mjs` and the `x-ember-nexus-recording` marker convention.

## Endpoints whose success example can't be sourced from a clean recording

- `docs/src/data/swagger/paths/system/get-graph-structure/get-graph-structure-200.json` — the
  recorded `nodeTypes`/`relationTypes` are polluted with leftover types from other tests sharing
  the same database (not a clean canonical example). Needs either an isolated test fixture or a
  curated/documented example that's explicitly marked as illustrative rather than a live recording.
- `docs/src/data/swagger/paths/user/post-token/post-token-201.json` — the recorded `token` value is
  a fresh random secret every run, inherently non-reproducible; same issue as the `instance` URN,
  but for a value that genuinely has no meaningful "real" content to normalize to.

## No recorded request body (so the HAR request example stays hand-authored)

The generic `ExampleGenerationController` mechanism only records *responses*; nothing captures the
request payload for these operations (unlike the search endpoints, which have dedicated
`request-payload.json` recordings). If that mechanism is ever extended to also record requests,
these should be wired the same way:

- `post-element`, `put-element`, `patch-element`
- `post-register`, `post-token`, `post-change-password`
- `post-element-file`, `put-element-file`
- `patch-upload`

## Shared problem examples: scope boundary (not a gap, just noting the decision)

Every endpoint's 401/403/404/412/400/409/416 response currently `$ref`s one shared example in
`docs/src/data/swagger/examples/*.json` rather than each endpoint having its own. Those shared
examples are now wired to one representative real recording each (see
`docs/scripts/generate-swagger.mjs`'s `normalizeProblemRecording`), which is correct since the
problem body is identical in shape/content regardless of which endpoint produced it - just noting
this deliberately stays a many-endpoints-to-one-example mapping rather than per-endpoint fragments.
