# TODO

Open work for `feature/gh-119-get-file-controller` and its follow-ups. Development notes, not permanent documentation:
delete entries once done or moved into GitHub issues. Aggregated 2026-09-26 from the former `TODO-NEXT.md`,
`analysis.md` and the 2026-09-26 morning session, then re-checked against the code.

Tracked as GitHub issues, not listed here: BLAKE3, S3 upload-bucket lifecycle, null properties in MongoDB/Elasticsearch,
phpmd reactivation, replacing MinIO, `cron:update-ownership` stub (#438). No ETag on upload endpoints is a final decision.

## Before merging into `main`

- **Verify CI on GitHub after pushing.** Feature jobs failed because `quay.io/minio/*` images were removed upstream;
  compose files now use pinned `pgsty/minio` / `pgsty/mc` (verified locally only). `test-feature` and the jobs behind it
  no longer wait for `test-mutant`. Also check the runner label `ubuntu-26.04`.
- **Verify the new test wiring in CI** (not runnable locally): the API instances of the feature and example
  compose files no longer mount the repository (test execution container `api-dev` mounts it, the prod image is used as
  shipped, so the dev profiler no longer exhausts `memory_limit` in the oversized-chunk tests); the example compose has a
  second instance `api-different-configuration` (prod image, only `configuration.yml` mounted over
  `config/packages/ember_nexus.yaml`, first request builds the cache) used by
  `composer test:example-generation-controller:with-different-configuration` via
  `API_DOMAIN=http://api-different-configuration`; `docker/.env.docker` now has the reference dataset's anonymous user.
- **Local test state (2026-09-27, dataset 0.0.33):** unit, phpstan, psalm, feature (604 + 8 command), example controller
  (130), example command (28) and server tests (4 scenarios) passed locally; command examples and three `docs/server`
  headers were regenerated. Do not commit a local `get-well-known-security-txt/200-response-body.txt` change if the
  example run rewrites it (the local mount differs from CI). CI must still confirm everything on the prod images.
- **Rewrite `CHANGELOG.md`** for the branch, with upgrade notes: reserved types `User`/`Token`/`Upload` rejected on
  `POST`; queue `REBUILD_SEARCH_DOCUMENT` replaced by `ELASTICSEARCH_UPDATE_OWNERSHIP`; legacy `file`/`hasFile` user
  properties get stuck after upgrading from main; `DELETE /upload` is `404` after access revocation;
  `Upload-Complete` mandatory on `PATCH /upload`; `405` instead of `500`; `instance` on all problem responses.

## Ownership recalculation (`cron:update-ownership`, #438) — design agreed, not implemented

No written design found in repo or docs (GitHub issue #438 not readable here: no `gh`). Facts: ES documents hold
`_groupsWithSearchAccess` / `_usersWithSearchAccess`, computed only on element create
(`CalculateSearchAccessEventListener`, uses the `AccessChecker::getDirect*WithAccessTo*` methods) and after backup load;
nothing recomputes when relations change later. Model for the consumer: `Cron/ReindexFilesCommand` + `QueueService`.
Design: re-run the existing calculation for an element, compare with the previous result (the ES values), store if it
changed and only then add the element's children to the queue (delta zero => stop, children are guaranteed unchanged).
Only `OWNS`, `HAS_SEARCH_ACCESS` and `IS_IN_GROUP` changes enqueue; `HAS_*_ACCESS` in general, `CREATED`, property-based
and implicit rules must not (cascade risk).

1. Fix `OwnershipChangeEventListener`: `in_array` instead of `array_key_exists`, type list reduced to the three types
   above. Payload needs relation id, type, start id, end id, event kind, `tries` (deleted relations can not be loaded
   later). Unit tests (none exist).
2. Queue: durable + persistent messages, `x-max-length`/dead-letter; changing arguments of an existing queue fails with
   `PRECONDITION_FAILED` (new name or delete on deploy).
3. `UpdateOwnershipCommand`: consume via `QueueService` (retry counter exists), work-list with visited set (cycles),
   recompute direct groups/users for the affected elements, compare as sets, store, enqueue children only on change,
   skip deleted elements, batch cap; register in `CronCommand` (5 min).
4. Tests: unit (delta zero, cycles, retries), feature (new `OWNS` edge -> search as new owner works, deleted edge removes
   access, group membership change). Docs: eventual consistency window up to the cron interval.
Open: behaviour during `LOADING_BACKUP` (create-time calculation is skipped, `ElementUpdateAfterBackupLoadEvent` fills
the gap); queue rename acceptable?; external consumers of the queue?

## Separate tickets (document only, not started)

- **Expired token cleanup:** cron job deleting expired (and long revoked) `Token` nodes after a grace period, including
  their file and uploads (same path as `DELETE /token`). Revoked tokens keep their file until then (decided: the file is
  still valid).
- **S3 orphan scanner:** cron command picking random files from S3 and checking they belong to an element in the
  database (else delete, log at notice level); feature flag to deactivate; sibling non-cron command scanning the whole
  bucket at once in pages of ~100 entries for admins. Also covers orphaned chunk objects and objects left behind by
  failed S3 deletes (which are now only logged).
- **`backup:load` retry:** no retry today (a failed file upload is reported and skipped). Add exponential backoff
  (1s, 2s, 4s, 8s, 16s) and a `--no-retry` flag.

## Verified open findings (2026-09-26)

- **Upload stuck after failed finalization** (S3 merge error, crash): the `Upload` stays `uploadComplete=true`, later
  `PATCH` gives `409`, no self-heal until cron deletes it. Orphan chunk objects if the process dies after the S3 chunk
  write but before the compare-and-set.
- Resumable finalize does not re-check `hasFile` for uploads started by `POST` (another request may have created a
  file meanwhile); `PUT` races are wanted.
- **Legacy `file` property** (non-array, with `hasFile: true`) from before this branch: `FilePropertyService` throws
  `500` on `GET`/`PUT`/`DELETE file` and even `DELETE /<uuid>`, `PATCH` can not repair it. Treat a non-array `file`
  as "no file" or provide a cleanup; mention in the upgrade notes.
- `If-Match` is not atomic with the write (accept and document).
- `ExceptionEventListener`: additional properties are spread after core fields; a key `status`/`type` overrides them.
- Monolog prints every Symfony error twice (`console` handler in `config/packages/monolog.yaml`); `408` log line lacks
  byte counts; `503` only has Caddy's access line. Chunked requests without `Content-Length` can not be detected as
  `408` (accepted; doc note).
- Not verified end to end: Caddy 15 min upload limit under a real throttled upload; 101 MiB chunks under
  `memory_limit=256M`; config cross-validation (min<=max chunk, bucket levels x length < 32, distinct buckets) is lazy.
- Observed once during the extension-less backup test: after `backup:create` + `backup:load` the reference token
  returned `401` because `Token.hash` was NULL in Neo4j. `BackupCreateCommand` is unchanged for tokens on this branch, so
  it is probably older behaviour; check against `main` and whether tokens are meant to survive a backup round trip.
- Checked, not problems: repeated header lines, `If-Match` + `If-None-Match` ordering, `Uuid::fromString` in
  `PropertyParseService` (HTTP paths catch it), Elasticsearch/Mongo writes per chunk (negligible).
- Done 2026-09-26 (for the changelog / docs): `HEAD /upload` `410` on expired uploads; consistency checks of
  `chunkIds`/`lastChunkId`/offset (`409`, upload deleted); completing-chunk length mismatch keeps the upload (digest
  mismatch still deletes it); `If-Range`; `POST /file` lock `file:create:<uuid>` (15 min, `409`, also `409` while an
  upload targets the element, `PUT` answers `409` while locked); token deletion removes its file and uploads; uploads on
  relations attached to a deleted node are removed; `DELETE` paths log S3 failures instead of failing; `database:drop`
  aborts multipart uploads and checks delete errors; `cron:delete-expired-uploads` pages, isolates failures, retries
  (1 h / 24 h backoff via Redis, gives up after 3); `CronCommand` isolates sub-commands; queue messages retry 3 times;
  camelCase problem keys; per-endpoint `Allow` header (`AllowHeaderResponseEventListener`, no global list anymore), `Content-Range` on `416`; `nosniff` on file `GET`; ETag keys are expired
  once after a delete; extension rules (see docs notes); Redis lock/retry keys use `RedisKeyFactory`; replaced files
  lose their old S3 object only after the graph flush (failure is logged).

## Refactoring (optional, not done)

`UploadFinalizationService` now exists; re-assess what remains before starting:

- `PatchUploadController` still likely has many dependencies (thin controller + append service).
- One chunk/upload validator shared by `UploadCreationService` and `PATCH`, running before any S3 write.
- Dedupe S3 operation factories (`FileOperation`, `UploadFileOperation`, `UploadFileChunkOperation`,
  `MergeFileChunksOperation`); move config cross-validation into the config tree.

## Test hygiene and gaps

- Several file/upload feature tests never delete the elements they create (`GetFileTest`,
  `PostFileInSingleRequestTest`, `PutFileInSingleRequestTest`, `ResumableUploadLifecycleTest`); relation setup is
  hand-written in `FileDigestOnRelationTest`, `FileTopLevelPropertyOnRelationTest`, `GetFileRangeOnRelationTest`
  instead of `BaseRequestTestCase::createEphemeralRelation()`; `*OnRelationTest` copies could be data providers.
- `BotanicalFileTest` and others modify the reference dataset, so each run needs `bin/test-feature-prepare`.
- Missing tests: `database:drop` S3 emptying, healthcheck S3 check, `file.*` configuration validation, dedicated
  405/409/410/412/416 endpoint tests, `getElementOrFail` and `QueueService::consumeQueue` failure paths, ETag with both
  conditional headers / `206` + ETag / `304` + Range, file ETag after `name` PATCH, feature tests for
  `cron:reindex-files` and `cron:delete-expired-uploads`.
- `database:drop`: the delete loop can spin if deletes fail silently; multipart uploads are not aborted.

## Repo leftovers

- `docs/open-api/swagger_old.json` / `swagger_old.yml` (unreferenced), `tests/UnitTests/Response` mirrors the old
  namespace (now `Type/Response`), `dozzle` uses `latest` plus docker.sock in `tools/docker-compose.yml`.
- Problem-JSON examples without the new `instance` member: `docs/open-api/swagger.json`, `swagger_old.*`,
  `examples/NotFoundProblemExample.json`, `paths/Element/GetElement.json`. Hand-written `429` examples store `status`
  as a string.

## For the docs rewrite (next-doc repo, `/home/syndesi/Projects/ember-nexus-next-doc/next-doc`)

- File and upload endpoints: access control (`UPDATE` for `POST`/`PUT`/`DELETE`, `READ` for `GET`, `404` instead of
  `403`), where `file.maxFileSizeInBytes` is enforced, workflow diagrams, status-code tables, one shared description of
  the extension keys; error pages 405/409/410/416 (408 and 503 exist); optional 429/503/bad-grammar pages.
- `405 Method Not Allowed` for existing routes with unsupported methods (was `500`).
- ETags: `If-None-Match: *` / `If-Match: *` on all ETag endpoints; no file ETag for an element without file, so
  `If-Match` on its file endpoints gives `412`; without the required access conditional requests give `404`.
- `DELETE /<uuid>/file` gives `404` for an element without file; `hasFile` decides.
- Uploads: per-attempt chunk keys (`<upload>-<index>-<chunkId>.wip`, ids in the `Upload` element's `chunkIds` in
  MongoDB), `PATCH /upload` compare-and-set on offset and last chunk id (`409` if lost), Redis lock as backstop,
  only `Repr-Digest` verified on the completing chunk, 15 min request limit for upload routes, resumable-upload
  behaviour on failed completion (see decision 1). "Chunk storage" section and the `patch-upload-409` swagger text
  are still missing; only a short note in `03-reference/08-upload.mdx` exists.
- `backup:create` warns (also with `--no-files`) about unfinished uploads: chunks not backed up, `Upload` nodes are
  (kept by decision), not resumable after `backup:load`. `backup:load` verifies hashes, skips unusable files with a
  warning (`--skip-verify`); single-request `POST`/`PUT` verify every supplied digest header.
- Operator docs: prod image `memory_limit=256M`, OPcache with preload, FrankenPHP `num_threads = 2 x cores`,
  `max_threads = 8 x cores` (`FRANKENPHP_NUM_THREADS` / `FRANKENPHP_MAX_THREADS`), `max_wait_time 30s` (worst case
  threads x `memory_limit`, ~16 GB at 64 threads accepted); body timeouts 30s, 15 min for upload routes
  (`API_REQUEST_BODY_TIMEOUT`, `API_UPLOAD_REQUEST_BODY_TIMEOUT`, `docker/Caddyfile`).
- Server tests (`bin/test-server`, `tests/ServerTests`, CI job `test-server`); `docs/server/` holds the expected
  Caddy/PHP error responses (`503`, `500`, out-of-memory, body timeouts). They mirror the headers of
  `public/index.php`: keep the `(problem_json_headers)` snippet in `docker/Caddyfile` in sync.
- Cut-off bodies answer `408` (`/error/408/request-timeout`). Every problem response carries `instance`
  (`urn:uuid:<id>`) = Monolog `requestId` = Caddy `request_id` = `uuid` in PHP error lines; not a response header;
  Caddy-created responses use a relative `type`.
- New behaviour to document: `410` on `HEAD` of expired uploads; `409` conflicts of `POST /file` (existing file, lock,
  upload in progress) and `PUT` `409` while `POST` holds the lock; completing-chunk length mismatch keeps the upload,
  digest mismatch deletes it; `If-Range`; camelCase extension keys (`expectedOffset`, `providedOffset`, `totalLength`,
  `requestedStart`, `requestedEnd`); per-endpoint `Allow` (also on `405`, no global list), `Content-Range: bytes */N` on `416`; `nosniff`; extension rules
  (missing/null/non-string property -> `bin`, empty string -> no extension and download name is the element name,
  filename without dot -> empty extension, no header -> `bin`, max 64 chars); cron retry behaviour.
- Docs pointers must not contain the word "todo" in the next-doc repo (its build test fails on it).
