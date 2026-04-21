# <span class="title-url"><span class="method-patch">PATCH</span>` /upload/<uuid>`</span><span class="title-human">Patch Upload Endpoint</span>

This endpoint is used to upload a new chunk to the resumable upload.

The new chunk can be of zero length - acting similar to the `HEAD /upload/<uuid>` endpoint, but also enabling the user
to complete an upload without transmitting additional chunks.

## Request Example

```bash
curl \
  -X PATCH
  -H "Authorization: Bearer secret-token:PIPeJGUt7c00ENn8a5uDlc" \
  https://api.localhost/74a8fcd9-6cb0-4b0d-8d42-0b6c3c54d1ac/file
```

<!-- tabs:start -->

### **🟢 Success 200**

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](./patch-upload/200-response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](./patch-upload/200-response-body.json ':include :type=code')
