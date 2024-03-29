- [Home](/)
- Getting started
  - [Tech stack](/getting-started/tech-stack)
  - [Hardware Requirements](/getting-started/hardware-requirements)
  - [Local Deployment](/getting-started/local-deployment)
  - [Configuration](/getting-started/configuration)
  - [Graph Database Fundamentals](/getting-started/graph-database-fundamentals)
- Security
  - [Ownership](/security/ownership)
  - [Access](/security/access)
  - [Workflow](/security/workflow)
  - [Token and Rate Limiting](/security/token-and-rate-limiting)
  - [Predefined Data Types](/security/predefined-data-types)
  - [Passwords, Tokens and Hashing](/security/passwords-tokens-and-hashing)
  - [Security Tests](/security/test/general)

- Concepts
  - [Authentication](/concepts/authentication)
  - [Caching](/concepts/caching)
  - [Pagination](/concepts/pagination)

- API Endpoints

  - **User Endpoints**
    - [<span class="method-post">POST</span>` /register -` Register New Account Endpoint](/api-endpoints/user/post-register)
    - [<span class="method-post">POST</span>` /change-password -` Change Password Endpoint](/api-endpoints/user/post-change-password)
    - [<span class="method-get">GET</span>` /me -` Get Me Endpoint](/api-endpoints/user/get-me)
    - [<span class="method-post">POST</span>` /token -` Create Token Endpoint](/api-endpoints/user/post-token)
    - [<span class="method-get">GET</span>` /token -` Get Token Endpoint](/api-endpoints/user/get-token)
    - [<span class="method-delete">DELETE</span>` /token -` Delete Token Endpoint](/api-endpoints/user/delete-token)
  - **Element Endpoints**
    - [<span class="method-get">GET</span>` / -` Get Index Endpoint](/api-endpoints/element/get-index)
    - [<span class="method-get">GET</span>` /<uuid> -` Get Element Endpoint](/api-endpoints/element/get-element)
    - [<span class="method-get">GET</span>` /<uuid>/parents -` Get Parents Endpoint](/api-endpoints/element/get-parents)
    - [<span class="method-get">GET</span>` /<uuid>/children -` Get Children Endpoint](/api-endpoints/element/get-children)
    - [<span class="method-get">GET</span>` /<uuid>/related -` Get Related Endpoint](/api-endpoints/element/get-related)
    - [<span class="method-post">POST</span>` / -` Create Root Level Element Endpoint](/api-endpoints/element/post-index)
    - [<span class="method-post">POST</span>` /<uuid> -` Create Element Endpoint](/api-endpoints/element/post-element)
    - [<span class="method-put">PUT</span>` /<uuid> -` Replace Element Endpoint](/api-endpoints/element/put-element)
    - [<span class="method-patch">PATCH</span>` /<uuid> -` Update Element Endpoint](/api-endpoints/element/patch-element)
    - [<span class="method-delete">DELETE</span>` /<uuid> -` Delete Element Endpoint](/api-endpoints/element/delete-element)
  - **File Endpoints**
    - [<span class="method-get">🚧 GET</span>` /<uuid>/file -` Get Element File Endpoint](/api-endpoints/file/get-element-file)
    - [<span class="method-post">🚧 POST</span>` /<uuid>/file -` Create Element File Endpoint](/api-endpoints/file/post-element-file)
    - [<span class="method-put">🚧 PUT</span>` /<uuid>/file -` Replace Element File Endpoint](/api-endpoints/file/put-element-file)
    - [<span class="method-patch">🚧 PATCH</span>` /<uuid>/file -` Update Element File Endpoint](/api-endpoints/file/patch-element-file)
    - [<span class="method-delete">🚧 DELETE</span>` /<uuid>/file -` Delete Element File Endpoint](/api-endpoints/file/delete-element-file)
  - **WebDAV Endpoints**
    - [<span class="method-copy">🚧 COPY</span>` /<uuid> -` Copy Element Endpoint](/api-endpoints/webdav/copy-element)
    - [<span class="method-lock">🚧 LOCK</span>` /<uuid> -` Lock Element Endpoint](/api-endpoints/webdav/lock-element)
    - [<span class="method-unlock">🚧 UNLOCK</span>` /<uuid> -` Unlock Element Endpoint](/api-endpoints/webdav/unlock-element)
    - [<span class="method-mkcol">🚧 MKCOL</span>` /<uuid> -` Create Folder Endpoint](/api-endpoints/webdav/mkcol-folder)
    - [<span class="method-move">🚧 MOVE</span>` /<uuid> -` Move Element Endpoint](/api-endpoints/webdav/move-element)
    - [<span class="method-propfind">🚧 PROPFIND</span>` /<uuid> -` Find Element Property Endpoint](/api-endpoints/webdav/propfind-element)
    - [<span class="method-proppatch">🚧 PROPPATCH</span>` /<uuid> -` Change Element Property Endpoint](/api-endpoints/webdav/proppatch-element)
  - **Search Endpoints**
    - [<span class="method-post">POST</span>` /search -` Search Endpoint](/api-endpoints/search/post-search)
  - **System Endpoints**
    - [<span class="method-get">GET</span>` /instance-configuration -` Get Instance Configuration Endpoint](/api-endpoints/system/get-instance-configuration)
    - [<span class="method-get">GET</span>` /.well-known/security.txt -` Get Well Known security.txt Endpoint](/api-endpoints/system/get-well-known-security-txt)
  - **Error Endpoints**
    - [<span class="method-get">GET</span>` /error/400/bad-content -` Get Details for Error 400 Bad Content Endpoint](/api-endpoints/error/get-400-bad-content)
    - [<span class="method-get">GET</span>` /error/400/forbidden-property -` Get Details for Error 400 Forbidden Property Endpoint](/api-endpoints/error/get-400-forbidden-property)
    - [<span class="method-get">GET</span>` /error/400/incomplete-mutual-dependency -` Get Details for Error 400 Incomplete Mutual Dependency Endpoint](/api-endpoints/error/get-400-incomplete-mutual-dependency)
    - [<span class="method-get">GET</span>` /error/400/missing-property -` Get Details for Error 400 Missing Property Endpoint](/api-endpoints/error/get-400-missing-property)
    - [<span class="method-get">GET</span>` /error/400/reserved-identifier -` Get Details for Error 400 Reserved Identifier Endpoint](/api-endpoints/error/get-400-reserved-identifier)
    - [<span class="method-get">GET</span>` /error/401/unauthorized -` Get Details for Error 401 Unauthorized Endpoint](/api-endpoints/error/get-401-unauthorized)
    - [<span class="method-get">GET</span>` /error/403/forbidden -` Get Details for Error 403 Forbidden Endpoint](/api-endpoints/error/get-403-forbidden)
    - [<span class="method-get">GET</span>` /error/404/not-found -` Get Details for Error 404 Not Found Endpoint](/api-endpoints/error/get-404-not-found)
    - [<span class="method-get">GET</span>` /error/412/precondition-failed -` Get Details for Error 412 Precondition Failed Endpoint](/api-endpoints/error/get-412-precondition-failed)
    - [<span class="method-get">GET</span>` /error/500/internal-server-error -` Get Details for Error 500 Internal Server Error Endpoint](/api-endpoints/error/get-500-internal-server-error)
    - [<span class="method-get">GET</span>` /error/501/not-implemented -` Get Details for Error 501 Not Implemented Endpoint](/api-endpoints/error/get-501-not-implemented)

- Commands
  - **System Commands**
    - [`healthcheck`](/commands/system/healthcheck)
  - **User Commands**
    - [`user:create`](/commands/user/user-create)
  - **Token Commands**
    - [`token:create`](/commands/token/token-create)
    - [`token:revoke`](/commands/token/token-revoke)
  - **Cache Commands**
    - [`cache:clear:etag`](/commands/cache/clear-etag)
  - **Backup Commands**
    - [`backup:list`](/commands/backup/backup-list)
    - [`backup:fetch`](/commands/backup/backup-fetch)
    - [`backup:create`](/commands/backup/backup-create)
    - [`backup:load`](/commands/backup/backup-load)
  - **Database Commands**
    - [`database:drop`](/commands/database/database-drop)
- Development
  - [Long Term Plans](/development/long-term-plans)
  - [Best Practices](/development/best-practices)
