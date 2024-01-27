# Caching

Most endpoints do support the `Etag`-header. The `Etag`-header-property is calculated from all involved elements UUIDs
and timestamps (microsecond precision).

The headers `If-Match` and `If-None-Match` are expected to be implemented soon, see their GitHub issues
([#232](https://github.com/ember-nexus/api/issues/232) and [#233](https://github.com/ember-nexus/api/issues/233))
for updates.

See also:

- [HTTP.dev's page on the Etag header](https://http.dev/etag)
- [MDN's page on the Etag header](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/ETag)
