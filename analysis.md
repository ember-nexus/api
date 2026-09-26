# Branch analysis: `feature/gh-119-get-file-controller` vs `main`

Scope: 801 files, +31.4k / -4.2k before the follow-up edits below (src +5.8k, tests +19.2k). Method: five area reviews (Sonnet, static), one cross-cutting pass (Opus), a doc-vs-code check against the next-doc, a code/doc quality assessment (Opus), then fixes and tests, ending with the full suites. Items already tracked in `TODO-NEXT.md` or upstream GitHub issues (BLAKE3, S3 lifecycle, null props, phpmd, MinIO replacement, `cron:update-ownership` stub, no ETag on upload endpoints) are not repeated. Feature-test cleanup is a non-issue (DB is reset per run).

## TLDR: PR-blocking gaps

**None remain.** Final state: unit tests 1075 (2 skipped), phpstan clean, feature suite 549 OK, feature `command` group OK, example-generation command tests OK (last full run before the final code round: 511 / 8 / 27). Everything is local and uncommitted.

Open decisions for the maintainer (not blocking) are under "Tomorrow / decisions". The most relevant ones: failed-completion behaviour (docs say resumable, code deletes), the now HTTP-unreachable >=500MB direct-upload path, and the never-firing `OwnershipChangeEventListener` (see below).

## Added / changed / removed (summary)

**Added**
- File endpoints on nodes and relations: `GET|POST|PUT|DELETE /{id}/file`. Range, `Repr-Digest`, digest verification on upload, Content-Disposition with ASCII fallback, stored sniffed mime type.
- `file` / `hasFile` element properties (top-level `file`, protected from direct writes, preserved on PUT/reset; Neo4j, Elasticsearch, Redis-ETag layers).
- Resumable uploads: `HEAD|PATCH|DELETE /upload/{id}` after the IETF resumable-upload draft. Incremental SHA-256 across chunks, S3 chunk storage + multipart merge, expiry, cleanup on element delete, per-upload Redis lock, cancellation on access loss, size caps on every body.
- S3 layer: client factory/wrapper, `S3Service`, technical-limits validator, UUID-nested bucket layout.
- File ETag (`EtagType::FILE`), `If-Match` / `If-None-Match` with `*`, 304/412, access-checked (404).
- New errors: 400 reserved-type, 405 (was 500), 409, 410, 412, 416; `additionalProperties` on problem+json; `getElementOrFail`.
- Config `file.*` (12 options) plus `file` block in `GET /instance-configuration`.
- CLI: `cron:delete-expired-uploads`, `cron:reindex-files`, `cron:update-ownership` (stub); `backup:create/load` with files (hash verification, `--skip-verify`, `--no-files`); `database:drop` empties S3 (`--no-files`); healthcheck checks S3.
- Queues: `ELASTICSEARCH_REINDEX_FILE_QUEUE`, `ELASTICSEARCH_UPDATE_OWNERSHIP`.
- Infra: Caddy/php.ini limits, minio-init, dozzle, `tools/generate-swagger.sh`, CI restructure. Large test additions.

**Changed**
- `src/Response/*` moved to `src/Type/Response/*` (`ElementResponse` / `CollectionResponse` split, Etag-capable `NotModifiedResponse`).
- `Server500LogicExceptionFactory` renamed `Server500LogicErrorExceptionFactory`.
- `DELETE /{id}` also removes S3 files and pending uploads.
- POST of reserved types `User`/`Token`/`Upload` (nodes and relation types) now rejected (behaviour change vs main).
- Queue `REBUILD_SEARCH_DOCUMENT` replaced by `ELASTICSEARCH_UPDATE_OWNERSHIP`.
- Backup fetch/list wiring, supercronic 2 to 5 min, pinned `pgsty/minio` images, regenerated `config/reference.php`, owns-relation error names `end` instead of `start`.

**Removed**
- `PatchElementFileController`, old `src/Response` locations, old queue name.
- Legacy behaviour where `file` / `hasFile` were ordinary user properties (upgrade note needed, X5).

## Edits made during this analysis (local, uncommitted)

**Bug fixes**
1. `S3Service`: rewind stream before multipart upload (single-request uploads >= 500MB always failed with 400). Unit test added.
2. `FileService`: strip `%` from the Content-Disposition fallback name. Symfony's `HeaderUtils::makeDisposition` throws for `%` in the fallback (`HeaderUtils.php:185`, `%` is the escape char of `filename*`), so names like "100% done" made `GET /file` return 500.
3. `BackupCreateCommand`: file query was a cartesian `OPTIONAL MATCH ... coalesce`; relation files were never exported and node ids duplicated. Now `UNION ALL` on `hasFile = true`, directed relation match, `ORDER BY` for paging.
4. `EmberNexusConfiguration`: chunk-size validation message named the wrong option.
5. `UploadCreationService`: `file.contentLength` from the actually uploaded length.
6. `PatchUploadController`: a failed size/digest check on completion now removes chunks and the Upload node.

**Decisions implemented after review**
7. Concurrency: IETF draft-12 4.6 says the server "MUST take measures to prevent race conditions, data loss and corruption from concurrent requests to append representation data" (SHOULD terminate the previous request). Implemented `UploadLockService` (Redis `SET NX PX`, token, Lua compare-and-delete, 15 min TTL); PATCH and DELETE take it, re-read the upload under the lock, and answer 409 while it is held. (This replaces the earlier "follow-up issue" verdict on B1: it was a spec violation.) Caveat: the newcomer is rejected instead of the earlier request being terminated, which the draft only recommends.
8. `Upload-Complete` is mandatory on PATCH (draft 4.4.1 MUST): missing gives 400. Optional on POST/PUT `/file` (absent = complete single request).
9. Size cap: every single-request body and PATCH chunk is bounded by `file.uploadMaxChunkSizeInBytes` (default 101MiB = Caddy/php limit), via `UploadBodyLimitService` (declared length upfront plus bounded stream read, so chunked transfer cannot bypass it). 400 above the cap. Larger files must use resumable uploads. (Resolves A-F3, B9, E4.)
10. Zero-length chunks: a non-final PATCH chunk of exactly 0 bytes returns 204 with current offset headers (status-check no-op; nothing stored, offset/hash unchanged, wrong offset still 409). 1..min-1 bytes stay 400 (S3 limit). Creation with an empty body follows the same rule.
11. Access loss cancels uploads: `CancelUploadsOnAccessLossEventListener` reviews on relation changes (`OWNS`, `IS_IN_GROUP`, `HAS_UPDATE_ACCESS` deleted/merged) and deleted users/groups, run in `kernel.response` (`ElementManager::flush()` cannot be nested); `UploadCancellationService` checks each owner's UPDATE access under the upload lock. Lazy path: HEAD and PATCH detect lost access, cancel and answer 404. Changes made outside the API (CLI, backup load) only trigger the lazy path. DELETE now yields 404 after access is revoked (the upload is already gone).
12. `Upload-Limit max-age` = remaining seconds (`max(0, expires - now)`).
13. Completing with total bytes different from `Upload-Length` gives 409 (same status as the existing "exceeds" case) and deletes chunks + Upload node. First-chunk-larger-than-`Upload-Length` is rejected on creation before the S3 write.
14. `backup:load`: skipped/failed files stay `hasFile=true` (intentional) but are now reported as errors on console (`[ERROR]`) and via the logger; exit code stays 0.

**Tests added**
- Digest verification e2e (`FileDigestVerificationTest`, 24 cases, node + relation): single-request POST/PUT with `Repr-Digest` and `Content-Digest`, positive and negative, replace keeps old file + ETag on mismatch; resumable completion positive and negative (mismatch: 400, upload gone, no file / old file unchanged).
- Feature: PATCH without `Upload-Complete`; zero-length chunk; upload-length enforcement; `Upload-Limit`/`Expires`; access-loss cancellation (eager, lazy via HEAD and PATCH, unrelated change keeps the upload).
- Backup round trip asserts the reference dataset's relation file (`5ed54a8f...`, `.txt`) is exported and restored (the reference dataset does contain one relation file; the earlier gap was that no test asserted it, which is why the broken query slipped through); tamper test asserts the error output.
- Unit: lock service, body-limit service, cancellation service, listener, delete controller, factories, S3 rewind. (The rewind test was not verified to fail without the fix.)

**Existing tests changed by decision** (not weakened): zero-byte chunk accepted; exceeding `Upload-Length` deletes the upload; DELETE after access revocation is 404; `max-age` expectations.

## Docs (next-doc only; old `docs/` untouched)

Edited: reference (file, upload, configuration), guide (big files, caching), file/upload endpoint pages, `database:drop` / `backup:load` / `cron` pages, placeholder error pages 405/409/410/416 (renumbered 01-16), swagger fragments (new error responses, lock 409, file POST 412) with regenerated `swagger.json`. Contradictions from the doc-vs-code check were fixed towards the decisions above. Not build-verified. **Note:** the site's `tests/quality/content/todo.test.ts` fails the build on the word "todo" in built markdown, so the pointers will trip it until resolved. Not done by the agents: the earlier agents never read the next-doc during the first review round (their prompts only referenced it); the dedicated doc-vs-code check ran afterwards.

## Quality assessment (code vs new docs)

Both about **3.5/5** and on a similar level: careful details, weaker overall structure; docs slightly ahead of the code (described behaviour that was not yet built, now largely closed).
- Code: follows main's conventions, comments explain why, S3Service is 487 lines. Weak points: upload logic duplicated between `UploadCreationService` and `PatchUploadController` (digest check byte-identical, chunk limits, file-property block, `fclose` workaround) which already drifted (validation after the S3 write on creation, event order differs); `PatchUploadController` has 20+ dependencies and heavy mock setup; `UploadCreationService` returns an HTTP Response; node/relation test copies vs data providers used inconsistently.
- Docs: clear guide/reference/endpoint split with curl examples. Weak points: no status-code tables, reference pages mix spec and narrative, no diagrams, error pages only partly present (placeholders now added).
- Full detail: session scratchpad `findings/H-quality.md`.

## Tomorrow / decisions

1. **Failed completion (digest mismatch):** code discards the upload; docs say it stays resumable. Decide (deletion after a completed-but-invalid upload is my recommendation, docs would then change).
2. **>=500MB direct-upload path in `S3Service::uploadFile`:** with the 101MiB HTTP cap it is only reachable from `backup:load`. Keep and document as backup-only, or drop. Tested threshold stays 500MB (accepted; only the rewind unit test, no huge-stream test).
3. **`database:drop`:** no tests for S3 emptying (loop can spin if deletes fail silently, multipart uploads not aborted, E7); no new tests for now.
4. **`OwnershipChangeEventListener`:** `array_key_exists($type, [list])` never matches (should be `in_array`). Fixing it switches on ownership-queue publishing for OWNS/CREATED/HAS_*_ACCESS changes, so check it together with the `cron:update-ownership` work before touching.
5. **Naming:** kebab-case problem+json extension keys (`expected-offset`, `total-length`) vs camelCase everywhere else; decide before the first release. `S3*` config keys are PascalCase.
6. **Missing headers:** 405 lacks `Allow` (RFC 9110); 416 lacks `Content-Range: bytes */N`.
7. **Docs:** resolve `TODO` markers (build test), share one description of extension keys once naming is decided, add status-code tables, diagrams, and pages for 429/503/bad-grammar if wanted.
8. **CHANGELOG / upgrade notes:** reserved types rejected on POST; queue rename; legacy `file`/`hasFile` user properties get stuck after upgrading from main (X5); DELETE-after-revoke and `Upload-Complete` mandatory on PATCH; 405 instead of 500.

### Refactoring suggestions (not done, by request)

- **PatchUploadController:** split into a thin controller plus an append service (lock, re-read, validate, append, complete). This removes the mock-heavy test setup and most PHPMD suppressions. Constructor setup should not merely be moved around; extract only where a real seam exists (append vs complete vs discard).
- **Upload validation:** one chunk/upload validator (min/max chunk, running total, `Upload-Length`, zero-length rule) used by both creation and PATCH, running before any S3 write. This also removes the creation-vs-PATCH drift and the orphan first-chunk (B6) risk.
- **Shared file-attach service:** one place for digest verification, `file`/`hasFile` property block, event dispatch order, and the resource-closing workaround.
- **S3 feature:** dedupe the chunk put / copy / multipart paths in `S3Service` and the operation factories (`FileOperation`, `UploadFileOperation`, `UploadFileChunkOperation`, `MergeFileChunksOperation` overlap heavily); consider one small value object for bucket/key/length instead of four near-identical types; move S3 config cross-validation (min<=max chunk, bucket levels x level length < 32, non-empty distinct buckets) into the config tree so misconfiguration fails at boot, not with a runtime 500.
- **Tests:** consolidate `*OnRelationTest` copies into data providers (`BaseRequestTestCase::createEphemeralRelation` already exists).

## Remaining minor findings (unchanged, not fixed)

| id | where | issue |
|---|---|---|
| D4 | `ExceptionEventListener.php:60` | additional properties are spread after core fields; a key `status`/`type` would override them |
| D6 | `PropertyParseService` | `Uuid::fromString` on stored data gives an unhandled 500 instead of a logic error |
| B4 | `PatchUploadController` | `Content-Digest` (per chunk per RFC 9530) is compared to the whole-file hash; honour `Repr-Digest` only |
| B7/E8 | cron commands | one bad upload aborts the batch; a failure blocks `reindex-files` that tick |
| B8 | `HeadUploadController` | HEAD on an expired, not yet cron-deleted upload returns 204 (`max-age=0`), PATCH returns 410 (now a tested behaviour) |
| C1 | ETag listeners | `If-None-Match` (prio 192) evaluated before `If-Match` (128): both headers can give 304 instead of 412 |
| C2/C3 | ETag / GET file | only first header line read; `If-Range` unsupported |
| C4/C5 | ETag design | expiry on PreDelete can re-cache; If-Match not atomic with the write |
| F4 | file replace/delete | event before flush, S3 first: failed flush leaves `hasFile` pointing at a missing object |
| F5 | `HeaderParseService` | extension unbounded/unvalidated (S3 key > 1024 gives 500) |
| F6 | `IncrementalHashService` | `unserialize` without `allowed_classes` |
| F7 | file GET | no `X-Content-Type-Options: nosniff` |
| F8 | `ElementFileDeletionService` | S3 failure after DB delete gives 500; log and continue |
| F9 | POST file | not atomic; concurrent POSTs both succeed |
| E5/E6/F10 | config | no cross-validation; validation is lazy (see refactoring) |
| X1 | `DeleteElementController` | uploads targeting attached relations are left to cron |
| X3 | `backup:create` | `:Upload` nodes exported without chunks; exclude the label |
| X4 | `DeleteTokenController` | files on Token/User nodes possible; `DELETE /token` leaves S3 objects; or reject file endpoints on reserved types |
| X5 | upgrade from main | legacy `file`/`hasFile` user properties get stuck |
| X6/D8 | Upload nodes | ES write and listeners on every chunk; skip label `Upload` |
| misc | | C7 unit test dir `UnitTests/Response` mirrors old namespace; `docs/open-api/swagger_old.{json,yml}` look like leftovers; `dozzle` with `latest` + docker.sock in dev compose; CI runner label `ubuntu-26.04` to verify on push |

Remaining test gaps (beyond the above): `database:drop` S3 deletion, healthcheck S3 check, Configuration tree / `file.*` validation, dedicated 405 and error-detail endpoint tests (405/409/410/412/416), D4 override case, `getElementOrFail` and `QueueService::consumeQueue` failure-path unit tests, ETag with both conditional headers / 206 + ETag / 304 + Range, file ETag after element `name` PATCH, `cron:reindex-files` feature test, cron delete-expired-uploads feature test, real-stream multipart (accepted).

## Details

Per-area reports with file:line and coverage matrices are in the session scratchpad `findings/` directory (`A-file`, `B-upload`, `C-etag`, `D-core`, `E-commands-infra`, `F-cross-cutting`, `G-doc-vs-code`, `H-quality`); they are temporary, so copy any you want to keep.
