export const HTTP_STATUS_CODE_FAMILY = {
  1: "informational",
  2: "successful",
  3: "redirection",
  4: "client error",
  5: "server error",
} as const;

export type HttpStatusCodeFamily = keyof typeof HTTP_STATUS_CODE_FAMILY;

export function getHttpStatusCodeFamilyLabel(
  family: HttpStatusCodeFamily,
): string {
  return HTTP_STATUS_CODE_FAMILY[family];
}

export const HTTP_STATUS_CODE = {
  200: "OK",
  201: "Created",
  204: "No Content",
  206: "Partial Content",
  304: "Not Modified",
  400: "Bad Request",
  401: "Unauthorized",
  403: "Forbidden",
  404: "Not Found",
  405: "Method Not Allowed",
  409: "Conflict",
  410: "Gone",
  412: "Precondition Failed",
  416: "Range Not Satisfiable",
  429: "Too Many Requests",
  500: "Internal Server Error",
} as const;

export type HttpStatusCode = keyof typeof HTTP_STATUS_CODE;

export function getHttpStatusCodeLabel(code: HttpStatusCode): string {
  return HTTP_STATUS_CODE[code];
}

export function getHttpStatusCodeFamily(
  code: HttpStatusCode,
): HttpStatusCodeFamily {
  return Math.floor(code / 100) as HttpStatusCodeFamily;
}
