# Exceptions

List of all exceptions:

- Internal exceptions: Thrown if there is a problem within the API, 5xx error codes
- External exceptions: Thrown if requests from the client are in any way problematic, 4xx error codes.
  - `400-bad-request`: Thrown if user supplied data is bad, incomplete, or problematic.
  - `401-unauthorized`: Thrown if supplied token is bad, or anonymous user does not exist.
  - `404-not-found`: Default error if data does not exist, or user has no permission to read it.
  - `405-method-not-allowed`: Returned if user has READ access, but access to current method.



| HTTP Status Code | Type                                      | Title                        | Example Detail (prod)                                        | Example Detail (dev)                                         |
| ---------------- | ----------------------------------------- | ---------------------------- | ------------------------------------------------------------ | ------------------------------------------------------------ |
| 400              | `/error/400/missing-property`             | Missing property             | Endpoint requires that the request contains property 'x' to be set to Y. | Endpoint requires that the request contains property 'x' to be set to Y. |
| 400              | `/error/400/forbidden-property`           | Forbidden Property           | Endpoint does not accept setting the property 'x' in the request. | Endpoint does not accept setting the property 'x' in the request. |
| 400              | `/error/400/incomplete-mutual-dependency` | Incomplete mutual dependency | Endpoint has mutual dependency on properties 'x' and 'y'. While property 'x' is set, property 'y' is missing. | Endpoint has mutual dependency on properties 'x' and 'y'. While property 'x' is set, property 'y' is missing. |
| 400              | `/error/400/reserved-identifier`          | Reserved identifier          | The requested identifier 'x' is reserved and can not be used. | The requested identifier 'x' is reserved and can not be used. |
| 400              | `/error/400/bad-content`                  | Bad content                  | Endpoint expects property 'x' to be Y, got 'z'.              | Endpoint expects property 'x' to be Y, got 'z'.              |
| 401              | `/error/401/unauthorized`                 | Unauthorized                 | Request does not contain valid token.                        | Request does not contain valid token.                        |
| 404              | `/error/404/not-found`                    | Not found                    | Requested element was not found.                             | Requested element was not found.                             |
| 405              | `/error/405/method-not-allowed`           | Method not allowed           | Endpoint does not support requested method, or you do not have sufficient permissions. | Endpoint does not support requested method, or you do not have sufficient permissions. |
| 429              | `/error/429/too-many-requests`            | Too many requests            | You have sent too many requests, please slow down.           | You have sent too many requests, please slow down.           |
| 500              | `/error/500/internal-server-error`        | Internal server error        | Internal server error, see log.                              | 'error message'.                                             |
| 501              | `/error/501/not-implemented`              | Not implemented              | Endpoint is currently not implemented.                       | Endpoint is currently not implemented.                       |
| 503              | `/error/503/service-unavailable`          | Service unavailable          | The service itself or an internal component is currently unavailable. | Service 'x' is currently unavailable.                        |
|                  |                                           |                              |                                                              |                                                              |
|                  |                                           |                              |                                                              |                                                              |

