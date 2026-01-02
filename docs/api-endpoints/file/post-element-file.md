# <span class="title-url"><span class="method-post">POST</span>` /<uuid>/file`</span><span class="title-human">Create Element File Endpoint</span>

Used to upload a smaller file directly to an element, or to create a resumable upload.

This endpoint requires that the element does not yet have a file associated to it.

## Request Example

```bash
curl \
  -X POST
  -H "Authorization: Bearer secret-token:PIPeJGUt7c00ENn8a5uDlc" \
  https://api.localhost/74a8fcd9-6cb0-4b0d-8d42-0b6c3c54d1ac/file
```

<!-- tabs:start -->

### **🟢 Success 200**

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](./post-element-file/200-response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](./post-element-file/200-response-body.json ':include :type=code')
