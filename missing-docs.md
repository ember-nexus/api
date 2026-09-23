# Missing docs

Documentation gaps noticed while working on the file/upload feature. Development notes, not permanent project
documentation — delete entries as they get written.

## Access control is undocumented across the file and upload endpoints

The three upload endpoints now carry an `Access Control` section (added alongside this file), but the four file
endpoints still document nothing about who may call them:

- `POST /<uuid>/file`, `PUT /<uuid>/file`, `DELETE /<uuid>/file` require **UPDATE** access on the element.
- `GET /<uuid>/file` requires **READ** access.
- All four answer `404` rather than `403` when access is missing, which is deliberate (it does not reveal that the
  element exists) and worth stating explicitly.

`docs/security/access.md` covers the access model in general but is not linked from any endpoint page.

## Endpoint workflow diagrams are missing for file and upload endpoints

Element endpoints (e.g. `docs/api-endpoints/element/delete-element.md`) each carry a mermaid workflow diagram
showing the token → rate limit → `If-Match` → existence → access → action decision chain. None of the seven
file/upload endpoint pages have one, even though their flows are more involved (chunk size limits, offset
matching, digest verification, upload completion).

## `file.maxFileSizeInBytes` enforcement is not described per endpoint

The limit is advertised via the `Upload-Limit` response header's `max-size` field and via
`/instance-configuration`, and is now enforced at four points: the declared `Upload-Length` when a resumable
upload is created, the running total on every chunk, the completing upload before its chunks are merged, and a
direct `POST`/`PUT /<uuid>/file`. Each rejects with `400 Bad Request`. Only the general `/reference/upload` prose
mentions it; the individual endpoint pages do not.

## ETag behaviour on `POST /<uuid>/file` is a trap worth documenting

`POST /<uuid>/file` evaluates `If-Match`/`If-None-Match`, but `If-Match` there can only ever fail:

- `POST /<uuid>/file` only targets an element which has no file yet — it answers `409 Conflict` otherwise.
- The `file` ETag of a file-less element is not obtainable by a client. `GET /<uuid>/file` answers `404` and
  therefore carries no `Etag` header, and `EtagCalculatorService::calculateFileEtag()` derives its own value
  rather than reusing the element ETag, so the ETag from `GET /<uuid>` does not match either.

So `If-Match` on `POST /<uuid>/file` is fail-closed: it can be rejected with `412`, never satisfied. `PUT` and
`DELETE /<uuid>/file` are unaffected, since their target already has a file and `GET /<uuid>/file` returns its
ETag. Either document the asymmetry or close it — `If-None-Match: *` is the RFC 9110 precondition for "create only
if absent" and is currently not handled (it is compared as a literal ETag value).

## Backup restore behaviour on unusable files

`backup:load` no longer aborts when a single file cannot be restored: files exceeding
`file.maxFileSizeInBytes` are skipped, upload failures are caught per file, and both are reported as warnings with
a count in the step summary. `docs/commands/` does not mention this.
