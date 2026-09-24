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
  `YML lint` job most likely failed on the missing newline at the end of `taskfile.yml`, which is fixed, but yamllint
  itself was not run locally, so other findings are possible. The controller example job is expected to fail, see
  above; nothing else has been run on GitHub yet.
  `test-feature` and the two jobs below it no longer wait for `test-mutant` in `ci-test.yml`, which still exists.
- **Rewrite `CHANGELOG.md`** for the branch.

## Missing tests

- `tests/ExampleGenerationCommand/Backup/BackupFilesTest.php` (create -> drop -> load round trip with files, hash
  mismatch skipped and `--skip-verify`) was written but never run; run `composer test:example-generation-command`.

## Test hygiene

- Several file and upload feature tests never delete the elements they create (e.g. `GetFileTest`,
  `PostFileInSingleRequestTest`, `PutFileInSingleRequestTest`, `ResumableUploadLifecycleTest`).
- Relation setup is written by hand in several tests (e.g. `FileDigestOnRelationTest`,
  `FileTopLevelPropertyOnRelationTest`, `GetFileRangeOnRelationTest`), instead of using
  `BaseRequestTestCase::createEphemeralRelation()`.
- `BotanicalFileTest` and many other tests permanently modify or delete reference dataset elements, so every run
  needs a fresh dataset (`bin/test-feature-prepare`); a rerun without reload fails with dozens of ETag and security
  tests.
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
- ETags: `If-None-Match: *` and `If-Match: *` are supported on all ETag endpoints. An element without file has no file
  ETag, so `If-Match` on its file endpoints answers `412`. Without the required access (`READ` for `GET`, `UPDATE` for
  the file endpoints and `PATCH`/`PUT`, `DELETE` for `DELETE /<uuid>`) conditional requests answer `404`, exactly like
  the endpoint itself.
- `DELETE /<uuid>/file` answers `404` for an element without file, and `hasFile` decides whether an element has one.
