# TODO

Notes for follow-up work which is intentionally not addressed yet in this session. Not meant as permanent
project documentation; fold into `docs/` (or delete) once actioned.

## Add Range header support to docs

`GET /<id>/file` now supports the HTTP `Range` request header (RFC 9110, Section 14.1.2), added in
`src/Service/FileRangeService.php`, `src/Type/ByteRange.php`, `src/Type/Response/BinaryStreamResponse.php` and
`src/Controller/File/GetElementFileController.php`. Behaviour, not yet documented under `docs/`:

- Supported forms: `bytes=<start>-<end>`, `bytes=<start>-` (open range, to the end of the file) and
  `bytes=-<suffixLength>` (last `<suffixLength>` bytes).
- Only a single range per request is supported. Multiple ranges (`bytes=0-10,20-30`) and syntactically invalid
  `Range` headers return `400 Bad Request` (Problem JSON, `error-400-bad-content`) — the server does not
  understand what was asked for.
- A satisfiable request returns `206 Partial Content` with `Content-Range: bytes <start>-<end>/<total>` and a
  `Content-Length` matching the range's size, not the full file's size.
- A syntactically valid but unsatisfiable range (e.g. `start` beyond the end of the file, empty file,
  zero/negative-length range) returns `416 Range Not Satisfiable` (Problem JSON, `error-416-range-not-satisfiable`),
  with `total-length` and, where applicable, `requested-start`/`requested-end` as additional detail properties.
- `end` is clamped to the file's last byte instead of erroring, if the client asks for more than is available.
- Responses now always advertise `Accept-Ranges: bytes`, including on full (200) responses.
- Only the requested range is read from S3 (via the `Range` parameter on `GetObject`), not the full file.

## High-performance BLAKE3 for file hashing

`file.hash` (`src/Service/FileHashService.php`) uses SHA-256 for now. BLAKE3 is the preferred long-term
algorithm, but the only real pure-PHP implementation (`tourze/blake3-php`) benchmarks at roughly 0.04 MB/s —
about 3000x too slow for a 1 Gb/s uplink — so it was not used. Getting real BLAKE3 performance requires either:

- A Docker image change adding a build stage that compiles the official BLAKE3 C reference implementation into
  a shared library, enabling the `ffi` PHP extension, and binding to it via FFI, or
- A compiled native PHP extension (e.g. `cypherbits/php-blake3`) added to the image build — smaller effort, but
  currently a low-adoption, single-maintainer project, a real supply-chain trust tradeoff for a crypto
  primitive that would need vetting first.

The `file.hash` property is already stored as `{<algorithm>: <digest>}` (not a flat pair) so adding/switching to
`file.hash.blake3` later needs no data migration — existing `file.hash.sha256` values simply stay valid
alongside it.

## Digest mismatch can overwrite an existing file before rejecting the upload

`UploadCreationService::setOrReplaceElementFileDirectly()` and `PatchUploadController::createFile()` currently
write the new file to S3 (single-part: promote to the storage bucket; resumable: merge chunks into the storage
bucket) *before* the hash is known and compared against a client-supplied `Repr-Digest`/`Content-Digest`. On a
mismatch, the new (rejected) bytes are cleaned up when this was a brand new file, but a *replace* of an existing
file reuses the same storage key, so a mismatch there can leave the element with no usable "original" to fall
back to (the old object may already be gone, e.g. if the extension changed). Not fixed in this session; options,
roughly in order of engineering cost:

1. **Verify before promoting to storage.** Single-part: hash while the bytes are being written to the
   intermediate S3 upload bucket (the step that already happens before the copy-to-storage step), so a mismatch
   never touches the storage bucket at all — this also removes the extra post-upload S3 read entirely.
   Resumable: at completion, hash each already-uploaded chunk by reading it back from the upload bucket (not the
   merged result), *before* calling `mergeFileChunks()` — same S3-read cost as today, just moved before the
   risky step instead of after, so a mismatch never touches the storage bucket. This is the most surgical fix
   and needs no new infrastructure.
2. **True streaming/incremental hash across chunks**, computed as each PATCH chunk arrives, with zero extra S3
   reads ever (not even the reorder in option 1). PHP's `hash_*` API has no serializable incremental state, so
   this needs either a small (SHA-256 state is compact and well-specified) custom incremental implementation, or
   piggybacks on whatever native crypto path eventually gets set up for BLAKE3 (see above) — same class of work,
   worth doing together.
3. **Stage-and-swap.** Before a replace, keep the previous storage object under a temporary key until the new
   upload's digest is verified; only delete the old object after a successful verification, otherwise discard
   the new one and leave the old one untouched. Cleanest "atomic replace" semantics, composes with option 1 or
   2, costs one extra S3 copy per replace.

## S3 upload bucket lifecycle policy

`cron:delete-expired-uploads` (and its `file.expiredUploadCanBeDeletedAfterExpirationInSeconds` grace period)
is an application-level cleanup of expired uploads and their S3 chunks. As defense in depth, the S3/MinIO
**upload** bucket itself should also be configured with an object lifecycle policy that expires objects after a
reasonably short time (e.g. 24-48h), in case the cron job is disabled, not running, or falls behind. This is an
infrastructure/ops concern (bucket provisioning lives outside this repository) and is not implemented here.
