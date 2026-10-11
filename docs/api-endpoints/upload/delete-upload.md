# <span class="title-url"><span class="method-delete">DELETE</span>` /upload/<uuid>`</span><span class="title-human">Delete Upload Endpoint</span>

Deletes a resumable upload, discarding any chunks already uploaded for it.

## Access Control

Only the user who created the upload may delete it; for anyone else the upload responds as if it did not exist.

Unlike `PATCH /upload/<uuid>` and `HEAD /upload/<uuid>`, this endpoint deliberately does **not** re-check access to
the upload's target element. Those two endpoints re-check it on every call, so an upload whose target became
inaccessible while it was in progress can no longer be inspected or continued. Deleting it is what remains
possible: an upload which can never be completed can always be cancelled by its owner, rather than being stuck
until `cron:delete-expired-uploads` eventually collects it.

## Request Example

```bash
curl \
  -X DELETE
  -H "Authorization: Bearer secret-token:PIPeJGUt7c00ENn8a5uDlc" \
  https://api.localhost/upload/74a8fcd9-6cb0-4b0d-8d42-0b6c3c54d1ac
```

<!-- tabs:start -->

### **🟢 Success 200**

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](./delete-upload/200-response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](./delete-upload/200-response-body.json ':include :type=code')
