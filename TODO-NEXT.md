# TODO (next)

Follow-up work surfaced while reviewing the file/upload branch, intentionally not addressed there. Same status as
`TODO.md`: development notes, not permanent project documentation. Fold into `docs/` or into issues once actioned.

## Carried over from `TODO.md` (still open)

### Enforce `file.maxFileSizeInBytes` at upload completion

`EmberNexusConfiguration::getFileMaxFileSizeInBytes()` is advertised to clients — via the `max-size` field of the
`Upload-Limit` response header (`NoContentResponseFactory.php`) and via `/instance-configuration`
(`GetInstanceConfigurationController.php`) — and is validated at startup against the storage backend's technical
maximum object size (`S3TechnicalLimitsValidator.php`). But nothing enforces it at request time: neither a direct
upload (`POST`/`PUT /<uuid>/file`) nor a completing resumable upload chunk (`PatchUploadController.php`) rejects a
file whose size exceeds `maxFileSizeInBytes`. The public API docs (`/reference/upload`) already describe a
completing upload that exceeds `max-size` as failing with `400 Bad Request`, so this needs a real check — most
naturally in `UploadCreationService::setOrReplaceElementFileDirectly()` for direct uploads and in
`PatchUploadController::createFile()` for resumable ones, before the file/merge is written.

### High-performance BLAKE3 for file hashing

`file.hash` (`src/Service/FileHashService.php`) uses SHA-256. BLAKE3 is preferred long-term, but the only real
pure-PHP implementation (`tourze/blake3-php`) benchmarks at roughly 0.04 MB/s — about 3000x too slow for a 1 Gb/s
uplink. Real BLAKE3 performance requires either:

- a Docker image change adding a build stage that compiles the official BLAKE3 C reference implementation into a
  shared library, enabling the `ffi` PHP extension, and binding to it via FFI, or
- a compiled native PHP extension (e.g. `cypherbits/php-blake3`) added to the image build — smaller effort, but
  currently a low-adoption, single-maintainer project, a real supply-chain trust tradeoff for a crypto primitive
  that would need vetting first.

`file.hash` is stored as `{<algorithm>: <digest>}` (not a flat pair), so adding or switching to `file.hash.blake3`
later needs no data migration — existing `file.hash.sha256` values stay valid alongside it.

Note: SHA-256 is *not* to be consolidated with the `sha3-256` used in `TokenGenerator.php`. Those are unrelated use
cases with opposite constraints, and RFC 9530's digest registry (which `DigestService` implements) has no entry for
SHA-3 — switching file hashing to SHA-3 would break `Repr-Digest`/`Content-Digest` interoperability.

### S3 upload bucket lifecycle policy

`cron:delete-expired-uploads` (and its `file.expiredUploadCanBeDeletedAfterExpirationInSeconds` grace period) is an
application-level cleanup of expired uploads and their S3 chunks. As defense in depth, the S3/MinIO **upload**
bucket should also carry an object lifecycle policy expiring objects after a short time (e.g. 24-48h), in case the
cron job is disabled, not running, or falling behind. Infrastructure/ops concern — bucket provisioning lives
outside this repository.

## New

### `BackupLoadCommand` cannot restore files larger than 5 GiB

Moved to its own note: see `TODO-BACKUP-LARGE-FILES.md`.

### `file` properties in collection and search responses — DONE

`file` is now returned everywhere an element is serialised. `ElementToRawService::elementToRaw()` lost its
`$includeFile` parameter, and the four call sites that passed `false` (`CollectionService` ×3,
`ElementHydrationSearchStepEventListener`) now get the same shape as the single-element endpoint. This also
resolves file properties in search results, with no new search step and no new query parameters.

Rationale: `file` is an ordinary element property in the MongoDB document fragment, which
`ElementManager::getNode()`/`getRelation()` fetch unconditionally — so `includeFile: false` never saved a database
round trip, only ~160 bytes of payload per file-bearing element. The property is exactly four keys, written in only
two places (`UploadCreationService.php:96`, `PatchUploadController.php:179`).

Accepted constraint: `file` is now part of the collection contract, so it must stay a small, fixed summary.
Thumbnails are expected to work like file downloads do today (implicit, link-free), further hash algorithms are
cheap to add, and storage keys / version history are explicitly out of scope. Reduced-payload responses can be
added later if the community asks.

Caching needed no change: both file-setting paths and the delete path call `merge()` + `flush()`, which bumps
`updated` via `UpdatedElementPreWriteEventListener` (collection etags hash `children.updated`,
`EtagCalculatorService.php:91`) and fires `ElementPostMergeEvent`, expiring the element plus all related collection
etag keys in `ExpireEtagOnChangeEventListener`.

### Null properties are removed from Neo4j but linger in MongoDB and Elasticsearch

Found while checking whether `DeleteElementFileController.php:63` (`addProperty('file', null)`) removes the
property. Verdict per store:

| Store | Write | Null removed? |
| --- | --- | --- |
| Neo4j | `SET node += $properties` (`NodeMergeToStatementEventListener.php:48`) | **Yes** — Cypher's `+=` deletes keys set to null |
| MongoDB | `$set` with the whole property array (`mongo-entity-manager/src/Type/EntityManager.php:110-119`) | **No** — stores an explicit `null`; would need `$unset` |
| Elasticsearch | `update` with `doc` (`elastic-entity-manager/src/Type/EntityManager.php:113-117`) | **No** — `null` stays in `_source`, though it is not indexed |

The API is nonetheless correct today: `GenericPropertyElementDefragmentizeEventListener` ends by stripping every
null property from the element after defragmentizing, so a stored `file: null` never reaches `elementToRaw()` and
`hasProperty('file')` is false. That is why the always-include-`file` change above needed no null guard.

What remains is storage cruft, not an API bug: deleted properties linger as explicit nulls in the Mongo document
and the ES `_source`. Fixing it properly means teaching `syndesi/mongo-entity-manager` to split null-valued
properties into an `$unset` clause (and the ES equivalent), which is an upstream change in those libraries rather
than something to work around here.

### Dead `$fileFragment` plumbing — DONE

Removed. `$fileFragment` was threaded through `FragmentGroup`, `FragmentHelper`, `ElementDefragmentizeService` and
all four fragmentize/defragmentize events, and was never read or written: the event factories hardcoded `null`, no
listener set it, `FragmentGroup::getFileFragment()` had no callers, `ElementManager::getNode()/getRelation()`
hardcoded `$fileFragment = null`, and none of the three defragmentize listeners read it. It predated this branch
(already present at the merge base).

It looks like a placeholder for treating S3 as a fourth backing store alongside Cypher/Mongo/Elastic. The
implementation went a different way: file metadata rides along as an ordinary `file` property in the Mongo
document, and S3 I/O happens explicitly in `S3Service`/`UploadCreationService` *outside* merge/flush — deliberately
so, per the reentrancy note in `UploadService::deleteUploadsTargeting()`. If S3 writes should ever join the flush
batch, reintroduce the seam deliberately and with a real type rather than `mixed`.
