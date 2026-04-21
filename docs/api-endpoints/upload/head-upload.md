# <span class="title-url"><span class="method-head">GET</span>` /upload/<uuid>`</span><span class="title-human">Head Upload Endpoint</span>

Queries the current state of the resumable upload.

## Request Example

```bash
curl \
  -X HEAD
  -H "Authorization: Bearer secret-token:PIPeJGUt7c00ENn8a5uDlc" \
  https://api.localhost/74a8fcd9-6cb0-4b0d-8d42-0b6c3c54d1ac/file
```

<!-- tabs:start -->

### **🟢 Success 200**

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](./head-upload/200-response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](./head-upload/200-response-body.json ':include :type=code')
