# TODO

Notes for follow-up work which is intentionally not addressed yet in this session. Not meant as permanent
project documentation; fold into `docs/` (or delete) once actioned.

## Enforce `file.maxFileSizeInBytes` at upload completion

`EmberNexusConfiguration::getFileMaxFileSizeInBytes()` is advertised to clients — via the `max-size` field of the
`Upload-Limit` response header (`NoContentResponseFactory.php`) and via `/instance-configuration`
(`GetInstanceConfigurationController.php`) — and is validated at startup against the storage backend's technical
maximum object size (`S3TechnicalLimitsValidator.php`). But nothing actually enforces it at request time: neither
a direct upload ([`POST`](src/Service/UploadCreationService.php)/`PUT /<uuid>/file`) nor a completing resumable
upload chunk (`PatchUploadController.php`) rejects a file whose size exceeds `maxFileSizeInBytes`. The public API
docs (`/reference/upload` on the docs site) now describe a completing upload that exceeds `max-size` as failing
with `400 Bad Request` — since that's the intended behavior, this needs an actual check (most naturally in
`UploadCreationService::setOrReplaceElementFileDirectly()` for direct uploads, and in
`PatchUploadController::createFile()` for resumable ones, before the file/merge is written) so the documented
behavior becomes true rather than aspirational.

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

## S3 upload bucket lifecycle policy

`cron:delete-expired-uploads` (and its `file.expiredUploadCanBeDeletedAfterExpirationInSeconds` grace period)
is an application-level cleanup of expired uploads and their S3 chunks. As defense in depth, the S3/MinIO
**upload** bucket itself should also be configured with an object lifecycle policy that expires objects after a
reasonably short time (e.g. 24-48h), in case the cron job is disabled, not running, or falls behind. This is an
infrastructure/ops concern (bucket provisioning lives outside this repository) and is not implemented here.



---

GetElementFileController:

        // older records (predating the 'mimeType' rename) use 'mimetype' instead
        $mimeType = $fileProperty['mimeType'] ?? $fileProperty['mimetype'] ?? null;
        if (!is_string($mimeType) || '' === $mimeType) {
            return null;
        }

pls remove the "old" property. the file feature as a whole unit is solely built in this branch -> no "old" logic exists in teh wild.

furthermore pls look through commits on this branch since august and check whether similar "old" fallback logic exists somewhere else.

---

CollectionService currently ignores file related special properties from response collections.
I think there are pros and cons for enabling and keeping them disabled. what you think? what would be better?
note: search endpoints can already be used to filter only elements with files.
does the search endpoint allow returning file properties directly, i.e. element collection with file? their element hydration search step is a bit special / behaves differently iirc.

---

iirc some code in this codebase uses sha3, while new file related stuff uses sha 256.
should I consolidate towards a single hash algorithm? if so, sha3?

---

pls remove all mentions of todo.md from the codebase. the todo.md file is solely used for development and temporary notes inside this branch; it should not be referenced by code later merged towards main.

---

s3service:

    /**
     * todo: optimize upload for larger files using multipart-upload?, handled by https://github.com/ember-nexus/api/issues/452.
     */
    public function uploadFile(UploadFileOperationInterface $uploadFileOperation): int
    {

should this be implemented now?

---

class BinaryStreamResponse extends StreamedResponse implements EtagCapableResponseInterface
{
public const int STREAM_CHUNK_SIZE = 8192;

is the stream chunk size of 8k ok? like is it performant? should it be increased? does it fit into common mtus etc.?

---

reduce code comments made in this branch since august to a minimum; remove them completely if they are just paraphrasing existing logic.

---

