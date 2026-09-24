# TODO (next)

Open work for the file/upload branch (`feature/gh-119-get-file-controller`) and its follow-ups. Development notes,
not permanent documentation: delete entries once they are done or moved into GitHub issues.

## Before merging into `main`

- **Controller example tests fail in CI.** `composer test:example-generation-controller` compares responses against
  snapshots in `docs/`, and 15 of them are stale:
  - 14 search examples (`docs/search/example/...`) still contain the footnote numbers which were removed in reference
    dataset 0.0.29, and lack the `file`/`hasFile` properties which are now always returned.
  - `System/GetGraphStructureTest` misses the `RELATED` relation type added in reference dataset 0.0.32.

  As `docs/` is replaced in the next branch, either regenerate these snapshots or temporarily allow the job to fail.
- **Verify CI on GitHub.** Everything passes locally (unit, feature, command example tests, phpstan, psalm, cs). The
  controller example job is expected to fail, see above; nothing else has been run on GitHub yet.
- **Rewrite `CHANGELOG.md`** for the branch.
- **Squash-merge.** The branch has 127 commits, most of them named `wip`.

## Decisions needed

- **`DELETE /<uuid>/file` on an element without a file** answers `204`, while `GET /<uuid>/file` answers `404`.
  Keep it idempotent, or answer `404`?
- **`If-None-Match: *` on `POST /<uuid>/file`** is compared as a literal ETag instead of meaning "only if no file
  exists" (RFC 9110). `If-Match` on this endpoint can never succeed, as a file-less element has no obtainable file
  ETag. Either document this or implement `*`.
- **Chunk size checks in `PatchUploadController`** run after the chunk has been uploaded to S3. The rejected chunk is
  removed together with the upload, but checking `Content-Length` before the upload would avoid the S3 round trip.
- **`PartialUploadRequest::getContentType()`** is parsed but never checked. Validate `application/partial-upload` as
  required by the resumable upload draft, or remove it.
- **`HasFilePropertyElementFragmentizeEventListener`** might be redundant, as the generic fragmentize listener
  already writes booleans to Neo4j. It additionally writes `hasFile` to MongoDB.

## Missing tests

High value:

- `backup:load`: a file whose content does not match `file.hash.sha256` is skipped, an element without `file.hash`
  is skipped, `--skip-verify` loads it anyway, an oversized file is skipped, and one failing file does not abort the
  restore.
- `backup:create` including files, ideally as a create → drop → load round trip.
- Unit tests for `PropertyParseService` (many `400` branches) and `UploadCreationService` (unsupported or mismatching
  digest, size limits on `Upload-Length` and direct uploads, chunk size limits on upload creation).
- Feature tests for `file.maxFileSizeInBytes`, e.g. in `ExampleGenerationControllerWithDifferentConfiguration` with a
  small limit: `POST`/`PUT /<uuid>/file`, an oversized `Upload-Length`, and a `PATCH` crossing the limit.
- Creating elements of the reserved types `User`, `Token` and `Upload` via `POST /` and `POST /<uuid>` answers `400`.

Medium:

- File ETags: `GET /<uuid>/file` with `If-None-Match` → `304`, `PUT`/`DELETE /<uuid>/file` with a stale `If-Match`
  → `412`, for nodes and relations.
- `PATCH /upload/<uuid>`: `410` for an expired upload, `409` when the data exceeds `Upload-Length`, `409` when the
  stored hash state can not be restored.
- Unit tests for `MongoDBNormalizedValueToRawValueEventListener` (BSONDocument fix) and for the Neo4j `true`
  placeholder of non-scalar properties in the generic (de)fragmentize listeners.

Low:

- Redis-before-live priority of the ETag listeners, `ElementService`, `S3ClientFactory`, `ContentDispositionWrapper`.
- Write access control tests for files and uploads on relations; only read access is tested on relations.

## Test hygiene

- Several file and upload feature tests never delete the elements they create (e.g. `GetFileTest`,
  `PostFileInSingleRequestTest`, `PutFileInSingleRequestTest`, `ResumableUploadLifecycleTest`).
- Relation setup is written by hand in several tests (e.g. `FileDigestOnRelationTest`,
  `FileTopLevelPropertyOnRelationTest`, `GetFileRangeOnRelationTest`), instead of using
  `BaseRequestTestCase::createEphemeralRelation()`.
- `BotanicalFileTest` permanently modifies reference dataset elements, so reruns need a dataset reload.
- 12 tests in `Security/Scenario02BasicPositiveTests/_02_01_ImmediateNodeOwnershipTest.php` are skipped (already on
  `main`). They cover owner access to the file and WebDAV endpoints and need to be rewritten.
- The command example tests still fetch reference dataset 0.0.19 (`BackupFetchTest`).

## Follow-up issues

- **`cron:update-ownership` is an empty stub** (see #438), and nothing in this repository consumes the
  `ELASTICSEARCH_UPDATE_OWNERSHIP_QUEUE` RabbitMQ queue.

## For the docs rewrite

- File and upload endpoints: document access control (`UPDATE` for `POST`/`PUT`/`DELETE`, `READ` for `GET`, `404`
  instead of `403`), where `file.maxFileSizeInBytes` is enforced, and add workflow diagrams like the element
  endpoints have.
- `backup:load` verifies file hashes, skips unusable files with a warning, and supports `--skip-verify`.
- `405 Method Not Allowed` is now returned for existing routes called with an unsupported method (was `500`).
