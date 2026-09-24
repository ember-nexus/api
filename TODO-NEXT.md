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
