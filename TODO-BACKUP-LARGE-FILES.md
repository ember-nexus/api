# TODO: `BackupLoadCommand` cannot restore files larger than 5 GiB

Development note, not permanent project documentation. Fold into `docs/` or into
https://github.com/ember-nexus/api/issues/452 once actioned.

## Problem

`S3Service::uploadFile()` writes the whole file with a single `putObject`, which caps it at S3's **5 GiB
single-object-upload limit**. It has two callers, and they are not equally bounded:

| Caller | Bound on input size |
| --- | --- |
| `UploadCreationService::setOrReplaceElementFileDirectly()` (`src/Service/UploadCreationService.php:89`) | `post_max_size` / `upload_max_filesize` — ~101 MB (`docker/php-embed/php.dev.ini:6-7`, `php.prod.ini:6-7`) |
| `BackupLoadCommand::loadFiles()` (`src/Command/BackupLoadCommand.php:216`) | **none** |

`BackupLoadCommand` streams files straight out of the backup archive (`$this->backupStorage->readStream($path)`)
into `uploadFile()`, with no PHP request limits involved. A backup containing a file larger than 5 GiB therefore
fails to restore.

The sharp edge: the default `file.maxFileSizeInBytes` is **10 GiB** (`docs/example/default-parameters.yaml:83`),
and files that large are accepted fine through the resumable upload path — which builds a real multipart upload in
`S3Service::mergeFileChunks()`. So the API will happily accept a file it cannot later restore from its own backup.
The failure only surfaces at restore time, which is the worst possible moment to discover it.

## Fix

Give `uploadFile()` a multipart path for large resources, rather than leaving multipart exclusive to
`mergeFileChunks()`. The machinery already exists in `S3Service`
(`createMultipartUploadFromMergeFileChunksOperation()`, `createUploadPartsFromMergeFileChunksOperation()`,
`completeMultipartUpload`, with `abortMultipartUpload` on failure) and needs generalising away from
`MergeFileChunksOperationInterface` so a plain resource can use it too.

Threshold: switch to multipart above some part-size boundary (S3's minimum part size is 5 MB, its single-PUT
maximum is 5 GiB), so ordinary API uploads keep taking the cheap single-PUT path.

Tracked in https://github.com/ember-nexus/api/issues/452.

## Test gap

No test covers a backup round trip with a file over the single-PUT limit — understandably, since a >5 GiB fixture
is not something to put in CI. Worth at least a unit-level test that the multipart branch is selected above the
threshold, rather than a real large-file integration test.
