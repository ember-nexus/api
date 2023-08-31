# Long-Term Plans

The Ember Nexus API is not intended to be modified or extended by third parties directly - we do not guarantee that
internal code is not refactored or creates breaking changes in minor version updates.

If you need to handle custom business logic or general automation, then it is recommended to create a separate
web app, which communicates with Ember Nexus over its API, which is stable and will not have breaking changes in minor
version updates.

## Version 2 of Ember Nexus API

While no guarantees can be given, we want to experiment if rewriting Ember Nexus API in Rust provides significant
performance benefits.  
This experiment starts after the release of version 1.0.0 and will be covered in separate blog
posts.

The API endpoints of version 2.0, regardless if the API uses Rust or not, will likely be highly similar.

## Native Support for HTTP 2 / HTTP 3

Neither HTTP 2 nor HTTP 3 will be supported during version 1.x. It is recommended to use a reverse proxy like
[Traefik](https://traefik.io/traefik/).

Support for version 2.x is likely.
