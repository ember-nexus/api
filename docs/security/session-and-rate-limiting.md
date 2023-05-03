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

## General Process of Validating a User's Session

<div id="graph-container-1" class="graph-container" style="height:1000px"></div>

<script>
renderWorkflow(document.getElementById('graph-container-1'), {
  nodes: [
    { id: 'init', ...workflowStart, label: 'server receives request' },
    { id: 'checkToken', ...workflowDecision, label: 'does request contain token?' },
    { id: 'noTokenAction', ...workflowStep, label: "use default anonymous\nuser for auth" },
    { id: 'isTokenPartOfRedis', ...workflowDecision, label: "is token already\nsaved in Redis?" },
    { id: 'tokenIsValid', ...workflowStep, label: "token is valid" },
    { id: 'isTokenInCypher', ...workflowDecision, label: "is token in Cypher?" },
    { id: 'loadTokenToRedis', ...workflowStep, label: "load token to Redis\n& set expiration date" },
    { id: 'tokenIsInvalid', ...workflowEndError, label: "token is invalid" },
    { id: 'checkRateLimit', ...workflowDecision, label: "is rate limit exceeded?" },
    { id: 'rateLimitExceeded', ...workflowEndError, label: "cancel request" },
    { id: 'handleRequest', ...workflowEndSuccess, label: "handle request" },
  ],
  edges: [
    { source: 'init', target: 'checkToken', label: '' },
    { source: 'checkToken', target: 'noTokenAction', label: 'no' },
    { source: 'checkToken', target: 'isTokenPartOfRedis', label: 'yes' },
    { source: 'isTokenPartOfRedis', target: 'tokenIsValid', label: 'yes' },
    { source: 'isTokenPartOfRedis', target: 'isTokenInCypher', label: 'no' },
    { source: 'isTokenInCypher', target: 'loadTokenToRedis', label: 'yes' },
    { source: 'loadTokenToRedis', target: 'tokenIsValid' },
    { source: 'isTokenInCypher', target: 'tokenIsInvalid', label: 'no' },
    { source: 'noTokenAction', target: 'checkRateLimit'},
    { source: 'tokenIsValid', target: 'checkRateLimit'},
    { source: 'checkRateLimit', target: 'handleRequest', label: 'no' },
    { source: 'checkRateLimit', target: 'rateLimitExceeded', label: 'yes' },
  ]
},
'TB');
</script>
