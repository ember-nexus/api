# Authentication

All API requests can either contain an `Authentication`-header or not.  
If the header is missing, then the request is executed with the permissions of the anonymous user, see
[configuration](../getting-started/configuration) for more details.

The `Authentication`-header contains a user token, e.g.:

```txt
Authorization: Bearer secret-token:M3WHIDj4q62EY0XiZFMLnv
```

## How to get a token

Tokens can be provided through the following ways:

1. Tokens can be generated through the [`POST /token` endpoint](../api-endpoints/user/post-token).
2. Tokens can be generated through the [`token:create` command](../commands/token/token-create).
3. Tokens without an expiration time can be created by removing the property `expirationDate` from token objects in the
   Neo4j database or backup files.  
   **Important**: This variant is highly discouraged for any production system.

## Example

```bash
curl \
  -X POST \
  -H "Content-Type: application/json" \
  -d '{"type": "Token", "uniqueUserIdentifier": "test@localhost.dev", "password": "1234"}' \
  https://api.localhost/token
```

Response:

<div class="code-title auto-refresh">Response Headers</div>

[Response Body](../api-endpoints/user/post-token/201-response-header.txt ':include :type=code')

<div class="code-title auto-refresh">Response Body</div>

[Response Body](../api-endpoints/user/post-token/201-response-body.json ':include :type=code')
