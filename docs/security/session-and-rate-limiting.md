# Session and Rate Limiting

## No Authentication Token

If a request does not contain an authentication token, then it uses the default anonymous user UUID for internal access
checks.  
The anonymous user UUID can be set with the environment variable `ANONYMOUS_USER_UUID`.

## Obtaining an Authentication Token

An authentication token can be obtained by [creating a new session](/api-endpoints/post-sessions), which requires
[creating an account](/api-endpoints/post-register) first.

All sessions and in turn authentication tokens have the same permissions, although the maximum lifetime of these tokens
may vary. Tokens can be invalidated in near real time (~1 second).

## Creating New Users with Limited Access

?> Ember Nexus API does not differentiate between humans, bots, artificial intelligences etc.

In order to create a new authentication token with limited permissions, you need to create a new user account and link
it accordingly:

> image of graph, before, after

```bash
# example of curl queries for creating such limited accounts
```
