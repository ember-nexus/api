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
  `Range` headers are ignored; the full file is served with `200 OK` instead.
- A satisfiable request returns `206 Partial Content` with `Content-Range: bytes <start>-<end>/<total>` and a
  `Content-Length` matching the range's size, not the full file's size.
- An unsatisfiable range (e.g. `start` beyond the end of the file, empty file, zero/negative-length range)
  returns `416 Range Not Satisfiable` as a Problem JSON response (`error-416-range-not-satisfiable`), with
  `total-length` and, where applicable, `requested-start`/`requested-end` as additional detail properties.
- `end` is clamped to the file's last byte instead of erroring, if the client asks for more than is available.
- Responses now always advertise `Accept-Ranges: bytes`, including on full (200) responses.
- Only the requested range is read from S3 (via the `Range` parameter on `GetObject`), not the full file.

## S3 upload bucket lifecycle policy

`cron:delete-expired-uploads` (and its `file.expiredUploadCanBeDeletedAfterExpirationInSeconds` grace period)
is an application-level cleanup of expired uploads and their S3 chunks. As defense in depth, the S3/MinIO
**upload** bucket itself should also be configured with an object lifecycle policy that expires objects after a
reasonably short time (e.g. 24-48h), in case the cron job is disabled, not running, or falls behind. This is an
infrastructure/ops concern (bucket provisioning lives outside this repository) and is not implemented here.
