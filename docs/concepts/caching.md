# Caching

There are three different cache related aspects in the Ember Nexus API:

- Internally used databases like Neo4j and Elasticsearch have their own caches. They might show slow cold start
  performance until their cache is initialized.
- Ember Nexus stores / caches some values like `Etag` values. They can be cleared and recalculated with dedicated
  [commands](../commands/cache/clear-etag).
- Clients using the API can cache data themselves, e.g. through using the `Etag`-header which is supplied by many `GET`
  endpoints.

## Client side caching

Most `GET` endpoints return the `Etag` header, which can then be used in later requests to determine whether content has
changed or stayed the same, i.e. whether data cached by the client is still valid.

Ember Nexus API supports the headers to determine whether cached content is still valid:

- `If-Match`, see [MDN's documentation](https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/If-Match).
- `If-None-Match`, see [MDN's documentation](https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/If-None-Match).

The value of the `Etag` header is calculated as the XXH3 hash from the identifiers and `updatedAt` timestamps of all
involved elements.  
As the `updatedAt` timestamp uses microsecond precision, the resulting `Etag` will be different as long as updates
happen at least one microsecond apart.  

See also:

- [HTTP.dev's page on the Etag header](https://http.dev/etag)
- [MDN's page on the Etag header](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/ETag)

## Example

### Initial request

The first `GET` request can be executed normally:

```bash
curl \
  -H "Authorization: Bearer secret-token:M3WHIDj4q62EY0XiZFMLnv" \
  https://api.localhost/35cd3b18-0d0c-4e98-876e-898b930797f2
```

The response then contains the `Etag`-header.  
Note that the **Etag itself contains quotes**; they are part of the Etag value.

<div class="code-title">Response Headers</div>

[Response Body](./caching/initial-request-response-header.txt ':include :type=code')

<div class="code-title">Response Body</div>

[Response Body](./caching/initial-request-response-body.json ':include :type=code')

### Cached request

The `GET` request can then be executed with an `If-None-Match` header. As the same `Etag` is used, it will fail with the
status code 304 not modified, which tells the client that the data returned by the initial request is still up to date:

```bash
curl \
  -H "Authorization: Bearer secret-token:M3WHIDj4q62EY0XiZFMLnv" \
  -H "If-None-Match: \"6JM8JahrCeu\"" \
  https://api.localhost/35cd3b18-0d0c-4e98-876e-898b930797f2
```

Response:

<div class="code-title">Response Headers</div>

[Response Body](./caching/not-modified-response-header.txt ':include :type=code')

The not modified response does not contain a response body.

### Cached request with invalid Etag

We can lastly modify the `Etag` value to another string, effectively invalidating it. The API will then see the mismatch
and return up-to-date data for the endpoint:

```bash
curl \
  -H "Authorization: Bearer secret-token:M3WHIDj4q62EY0XiZFMLnv" \
  -H "If-None-Match: \"invalid\"" \
  https://api.localhost/35cd3b18-0d0c-4e98-876e-898b930797f2
```

Response:

<div class="code-title">Response Headers</div>

[Response Body](./caching/initial-request-response-header.txt ':include :type=code')

<div class="code-title">Response Body</div>

[Response Body](./caching/initial-request-response-body.json ':include :type=code')
