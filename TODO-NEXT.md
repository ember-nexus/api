# TODO (next)

Open work for the file/upload branch (`feature/gh-119-get-file-controller`) and its follow-ups. Development notes,
not permanent documentation: delete entries once they are done or moved into GitHub issues.

## Before merging into `main`

- **Verify CI on GitHub after pushing.** The feature jobs failed because `quay.io/minio/*` images were removed
  upstream; `tools/docker-compose.yml` and `tests/FeatureTests/docker-compose-neo4j-*.yml` now use the pinned
  `pgsty/minio` and `pgsty/mc` images (verified locally with the init script, not yet on GitHub). The feature jobs and
  everything behind them have not run on GitHub yet.
  `test-feature` and the two jobs below it no longer wait for `test-mutant` in `ci-test.yml`, which still exists.
- **Rewrite `CHANGELOG.md`** for the branch.

## Test hygiene

- Several file and upload feature tests never delete the elements they create (e.g. `GetFileTest`,
  `PostFileInSingleRequestTest`, `PutFileInSingleRequestTest`, `ResumableUploadLifecycleTest`).
- Relation setup is written by hand in several tests (e.g. `FileDigestOnRelationTest`,
  `FileTopLevelPropertyOnRelationTest`, `GetFileRangeOnRelationTest`), instead of using
  `BaseRequestTestCase::createEphemeralRelation()`.
- `BotanicalFileTest` and many other tests permanently modify or delete reference dataset elements, so every run
  needs a fresh dataset (`bin/test-feature-prepare`); a rerun without reload fails with dozens of ETag and security
  tests.

## Follow-up issues

- **Replace MinIO** as S3 server (upstream stopped publishing images); `pgsty/minio` is only a stopgap.
- **`cron:update-ownership` is an empty stub** (see #438), and nothing in this repository consumes the
  `ELASTICSEARCH_UPDATE_OWNERSHIP_QUEUE` RabbitMQ queue.

## For the docs rewrite

- File and upload endpoints: document access control (`UPDATE` for `POST`/`PUT`/`DELETE`, `READ` for `GET`, `404`
  instead of `403`), where `file.maxFileSizeInBytes` is enforced, and add workflow diagrams like the element
  endpoints have.
- `backup:load` verifies file hashes, skips unusable files with a warning, and supports `--skip-verify`.
- `405 Method Not Allowed` is now returned for existing routes called with an unsupported method (was `500`).
- ETags: `If-None-Match: *` and `If-Match: *` are supported on all ETag endpoints. An element without file has no file
  ETag, so `If-Match` on its file endpoints answers `412`. Without the required access (`READ` for `GET`, `UPDATE` for
  the file endpoints and `PATCH`/`PUT`, `DELETE` for `DELETE /<uuid>`) conditional requests answer `404`, exactly like
  the endpoint itself.
- `DELETE /<uuid>/file` answers `404` for an element without file, and `hasFile` decides whether an element has one.
- Uploads: every chunk attempt has its own object key (`<upload>-<index>-<chunkId>.wip`, ids stored in the `Upload`
  element's `chunkIds`) and `PATCH /upload` appends with a compare-and-set on offset and last chunk id (graph property; the `chunkIds` list lives in MongoDB) (`409` if lost);
  only `Repr-Digest` is verified on the completing chunk. Only a short note in `03-reference/08-upload.mdx`
  ("Concurrency", "Verifying integrity") exists, "Chunk storage" and the `patch-upload-409` swagger text still need it.
- `backup:create` warns (also with `--no-files`) about unfinished uploads: their chunks are not backed up, the `Upload`
  nodes are, and can not be resumed after `backup:load`. `docs/` and the command docs (`backup-create.mdx`) do not
  mention this yet. Single-request `POST`/`PUT` verify every supplied `Repr-Digest`/`Content-Digest` header (same bytes).
- Operator docs: the production image runs with `memory_limit=256M`, OPcache with preload and no timestamp validation,
  and FrankenPHP threads `num_threads = 2 x cores`, `max_threads = 8 x cores` (both overridable via the environment
  variables `FRANKENPHP_NUM_THREADS` / `FRANKENPHP_MAX_THREADS`), `max_wait_time 30s`. Request body timeouts: 30s, 15 min
  for the upload endpoints (`docker/Caddyfile`). Worst case memory is threads x `memory_limit`.
- Server tests (`bin/test-server`, `tests/ServerTests`, CI job `test-server`): run the production image with lowered
  limits (env variables of `docker/Caddyfile`) one scenario after another. `docs/server/` holds the expected special
  server error responses (`503` no free PHP thread, `500` PHP failure outside of the application, cut-off bodies) for the docs.
