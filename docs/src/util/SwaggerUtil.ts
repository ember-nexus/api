import equal from "fast-deep-equal/es6";
import type { HarRequest } from "httpsnippet";
import {
  type ContentObject,
  type HeaderObject,
  type OpenAPIObject,
  type OperationObject,
  type ParameterObject,
  type PathItemObject,
  type ReferenceObject,
  type ResponseObject,
  type ResponsesObject,
  type SchemaObject,
  type SecuritySchemeObject,
  isReferenceObject,
} from "openapi3-ts/oas31";

import type {
  HttpStatusCode,
  Link,
  RequestBody,
  RequestBodyContent,
  RequestExample,
  RequestHeader,
  RequestParameter,
  ResponseExample,
  ResponseHeader,
  Schema,
} from "../type";

/**
 * Resolve the operation object for a given path + HTTP method.
 *
 * `PathItemObject` keys are typed per method (get/post/...), so indexing with a
 * free-form `method` string requires a narrowing cast.
 */
function getOperation(
  spec: OpenAPIObject,
  path: string,
  method: string,
): OperationObject | undefined {
  const pathItem = spec.paths?.[path];
  return pathItem?.[method as keyof PathItemObject] as
    OperationObject | undefined;
}

/**
 * Collect the effective parameter list for an operation: parameters declared on
 * the path item (shared by every method) merged with those on the operation,
 * with the operation-level entry winning on a (name, in) collision — per the
 * OpenAPI specification. References are skipped defensively; in practice the
 * spec is dereferenced before it reaches here.
 */
function resolveParameters(
  spec: OpenAPIObject,
  path: string,
  method: string,
): ParameterObject[] {
  const pathItem = spec.paths?.[path] as PathItemObject | undefined;
  const op = getOperation(spec, path, method);

  const concrete = (
    params: PathItemObject["parameters"] = [],
  ): ParameterObject[] =>
    params.filter((p): p is ParameterObject => !isReferenceObject(p));

  const byKey = new Map<string, ParameterObject>();
  // Path-level first, then let operation-level overwrite on collision.
  for (const p of concrete(pathItem?.parameters)) {
    byKey.set(`${p.in}:${p.name}`, p);
  }
  for (const p of concrete(op?.parameters)) {
    byKey.set(`${p.in}:${p.name}`, p);
  }
  return [...byKey.values()];
}

/** Render a value as a short display string. */
function stringifyValue(value: unknown): string | null {
  if (value === undefined) {
    return null;
  }
  if (typeof value === "object") {
    return JSON.stringify(value);
  }
  return String(value);
}

/**
 * Turn the validation keywords of a schema into human-readable chips, e.g.
 * `["≥ 1", "default 100"]`.
 */
function describeConstraints(schema: SchemaObject | undefined): string[] {
  if (!schema) {
    return [];
  }
  const chips: string[] = [];

  if (schema.default !== undefined) {
    chips.push(`default ${stringifyValue(schema.default)}`);
  }
  if (typeof schema.minimum === "number") {
    chips.push(`≥ ${schema.minimum}`);
  }
  if (typeof schema.maximum === "number") {
    chips.push(`≤ ${schema.maximum}`);
  }
  if (typeof schema.exclusiveMinimum === "number") {
    chips.push(`> ${schema.exclusiveMinimum}`);
  }
  if (typeof schema.exclusiveMaximum === "number") {
    chips.push(`< ${schema.exclusiveMaximum}`);
  }
  if (typeof schema.minLength === "number") {
    chips.push(`min length ${schema.minLength}`);
  }
  if (typeof schema.maxLength === "number") {
    chips.push(`max length ${schema.maxLength}`);
  }
  if (typeof schema.pattern === "string") {
    chips.push(`pattern ${schema.pattern}`);
  }
  if (Array.isArray(schema.enum)) {
    chips.push(
      `one of: ${schema.enum.map((v) => stringifyValue(v)).join(", ")}`,
    );
  }
  return chips;
}

export function extractCommonRequestHeaders(
  spec: OpenAPIObject,
  path: string,
  method: string,
): RequestHeader[] {
  const op = getOperation(spec, path, method);

  return (op?.parameters ?? [])
    .filter((p) => !isReferenceObject(p) && p.in === "header")
    .map((p): RequestHeader => {
      // Narrowed above: refs are filtered out, so `p` is a ParameterObject.
      const param = p as Exclude<typeof p, { $ref: string }>;
      return {
        header: param.name,
        presence: param.required ? "required" : "optional",
        description: param.description,
        links: (param["x-ember-nexus-links"] as Link[] | undefined) ?? [],
      };
    });
}

export function extractAuthHeaders(
  spec: OpenAPIObject,
  path: string,
  method: string,
): RequestHeader[] {
  const op = getOperation(spec, path, method);
  const security = op?.security ?? [];
  const schemes = spec.components?.securitySchemes ?? {};

  const isOptional = security.some((req) => Object.keys(req).length === 0);

  return security
    .flatMap((requirement) => Object.keys(requirement))
    .map((name) => schemes[name])
    .filter(
      (scheme): scheme is SecuritySchemeObject =>
        scheme !== undefined &&
        !isReferenceObject(scheme) &&
        scheme.type === "http" &&
        scheme.scheme === "bearer",
    )
    .map((scheme): RequestHeader => ({
      header: "Authorization",
      presence: isOptional ? "optional" : "required",
      description: scheme.description ?? "Bearer token for authentication.",
      links: (scheme["x-ember-nexus-links"] as Link[] | undefined) ?? [],
    }));
}

export function extractRequestHeaders(
  spec: OpenAPIObject,
  path: string,
  method: string,
): RequestHeader[] {
  return [
    ...extractAuthHeaders(spec, path, method),
    ...extractCommonRequestHeaders(spec, path, method),
  ];
}

/**
 * Extract the non-header request parameters (path + query + cookie) for an
 * operation. Header parameters are intentionally excluded — they are surfaced
 * via `extractRequestHeaders`.
 */
export function extractRequestParameters(
  spec: OpenAPIObject,
  path: string,
  method: string,
): RequestParameter[] {
  return resolveParameters(spec, path, method)
    .filter(
      (p): p is ParameterObject & { in: "path" | "query" | "cookie" } =>
        p.in === "path" || p.in === "query" || p.in === "cookie",
    )
    .map((param): RequestParameter => {
      const schema =
        param.schema && !isReferenceObject(param.schema)
          ? (param.schema as SchemaObject)
          : undefined;

      const type = schema?.type;

      return {
        name: param.name,
        location: param.in,
        // Path parameters are always required per the OpenAPI spec.
        presence:
          param.in === "path" || param.required ? "required" : "optional",
        description: param.description ?? "",
        type: Array.isArray(type) ? type.join(" | ") : (type ?? null),
        format: schema?.format ?? null,
        defaultValue: stringifyValue(schema?.default),
        example: stringifyValue(param.example ?? schema?.example),
        constraints: describeConstraints(schema),
        links: (param["x-ember-nexus-links"] as Link[] | undefined) ?? [],
      };
    });
}

/**
 * Build a representative example value for a schema. Resolution order: an
 * explicit `example` on the schema, otherwise — for objects — an object
 * assembled from the immediate properties' own `example`s. Intentionally
 * shallow: this is a presentation helper, not a full example generator.
 */
function exampleFromSchema(schema: SchemaObject | undefined): unknown {
  if (!schema) {
    return undefined;
  }
  if (schema.example !== undefined) {
    return schema.example;
  }
  if (schema.type === "object" && schema.properties) {
    const obj: Record<string, unknown> = {};
    for (const [key, propOrRef] of Object.entries(schema.properties)) {
      if (isReferenceObject(propOrRef)) {
        continue;
      }
      if (propOrRef.example !== undefined) {
        obj[key] = propOrRef.example;
      }
    }
    return Object.keys(obj).length > 0 ? obj : undefined;
  }
  return undefined;
}

/**
 * Extract the request body of an operation as a flat list of content-type
 * variants. Every declared content type is surfaced at once (no switcher);
 * each carries its schema and a best-effort example. Returns `null` when the
 * operation declares no body.
 */
export function extractRequestBody(
  spec: OpenAPIObject,
  path: string,
  method: string,
): RequestBody | null {
  const op = getOperation(spec, path, method);
  const requestBody = op?.requestBody;

  if (!requestBody || isReferenceObject(requestBody)) {
    return null;
  }

  const content: ContentObject = requestBody.content ?? {};

  const contents: RequestBodyContent[] = Object.entries(content).map(
    ([mimeType, mediaType]): RequestBodyContent => {
      const schema =
        mediaType.schema && !isReferenceObject(mediaType.schema)
          ? (mediaType.schema as SchemaObject)
          : undefined;

      // Example resolution: media-type `example` → first named `examples`
      // entry → synthesized from the schema.
      let exampleValue: unknown = mediaType.example;
      if (exampleValue === undefined && mediaType.examples) {
        const named = Object.values(mediaType.examples).find(
          (e) => !isReferenceObject(e),
        );
        exampleValue =
          named && !isReferenceObject(named) ? named.value : undefined;
      }
      if (exampleValue === undefined) {
        exampleValue = exampleFromSchema(schema);
      }

      const isJson = mimeType.includes("json");

      return {
        mimeType,
        schema: mediaType.schema
          ? JSON.stringify(mediaType.schema, null, 2)
          : null,
        example:
          exampleValue === undefined
            ? null
            : {
                content: isJson
                  ? JSON.stringify(exampleValue, null, 2)
                  : String(exampleValue),
                type: isJson ? "json" : "plain",
              },
      };
    },
  );

  return {
    required: requestBody.required ?? false,
    description: requestBody.description ?? "",
    contents,
  };
}

export function extractHarExamples(
  spec: OpenAPIObject,
  path: string,
  method: string,
): RequestExample[] {
  const op = getOperation(spec, path, method);
  if (!op || !("x-ember-nexus-har-example" in op)) {
    return [];
  }
  const examples = op["x-ember-nexus-har-example"] as {
    name?: string;
    har: Partial<HarRequest>;
  }[];
  return examples.map((example) => ({
    name: example.name ?? null,
    har: example.har,
  }));
}

export function extractResponseHeaders(
  spec: OpenAPIObject,
  path: string,
  method: string,
): ResponseHeader[] {
  const op = getOperation(spec, path, method);
  const merged: { name: string; header: HeaderObject; codes: string[] }[] = [];

  const responses = (op?.responses ?? {}) as ResponsesObject;
  for (const [code, responseOrRef] of Object.entries(responses)) {
    if (isReferenceObject(responseOrRef)) {
      continue;
    }
    const response = responseOrRef as ResponseObject;

    for (const [name, headerOrRef] of Object.entries(response.headers ?? {})) {
      if (isReferenceObject(headerOrRef)) {
        continue;
      }
      const header = headerOrRef;

      const existing = merged.find(
        (entry) => entry.name === name && equal(entry.header, header),
      );
      if (existing) {
        existing.codes.push(code);
      } else {
        merged.push({ name, header, codes: [code] });
      }
    }
  }

  return merged.map(({ name, header }): ResponseHeader => {
    const headerSchema =
      header.schema && !isReferenceObject(header.schema)
        ? (header.schema as SchemaObject)
        : undefined;

    return {
      header: name,
      presence: header.required ? "always" : "optional",
      important:
        (header["x-ember-nexus-important"] as boolean | undefined) ?? false,
      description: header.description ?? "",
      example: stringifyValue(headerSchema?.example) ?? "",
      links: (header["x-ember-nexus-links"] as Link[] | undefined) ?? [],
    };
  });
}

/**
 * Name of the `components.schemas` entry a media-type schema is a bare
 * `$ref` to, taken from an *undereferenced* spec. `null` for an
 * inline/anonymous schema, or when no schema is given at all.
 */
function schemaRefName(
  schema: SchemaObject | ReferenceObject | undefined,
): string | null {
  if (!schema || !isReferenceObject(schema)) {
    return null;
  }
  const match = /^#\/components\/schemas\/([^/]+)$/.exec(schema.$ref);
  return match ? match[1] : null;
}

export function extractResponseExamples(
  spec: OpenAPIObject,
  path: string,
  method: string,
  /**
   * The same operation, taken from an undereferenced parse of the spec.
   * Optional — only needed to populate `schemaRefName` for markdown
   * rendering; the HTML render target doesn't pass it.
   */
  rawSpec?: OpenAPIObject,
): ResponseExample[] {
  const op = getOperation(spec, path, method);
  const rawOp = rawSpec ? getOperation(rawSpec, path, method) : undefined;
  const results: ResponseExample[] = [];

  const responses = (op?.responses ?? {}) as ResponsesObject;
  for (const [statusCode, responseOrRef] of Object.entries(responses)) {
    if (isReferenceObject(responseOrRef)) {
      continue;
    }
    const response = responseOrRef as ResponseObject;

    const headerNames = Object.keys(response.headers ?? {});
    const headers = headerNames
      .map((name) => {
        const headerOrRef = response.headers?.[name];
        let example = "";
        if (headerOrRef && !isReferenceObject(headerOrRef)) {
          const headerSchema = headerOrRef.schema;
          if (headerSchema && !isReferenceObject(headerSchema)) {
            example = String(headerSchema.example ?? "");
          }
        }
        return `${name}: ${example}`;
      })
      .join("\n");

    const content: ContentObject = response.content ?? {};
    const rawContent = (rawOp?.responses as ResponsesObject | undefined)?.[
      statusCode
    ];
    const rawResponseContent: ContentObject | undefined =
      rawContent && !isReferenceObject(rawContent)
        ? (rawContent as ResponseObject).content
        : undefined;
    const links = (response["x-ember-nexus-links"] as Link[] | undefined) ?? [];

    if (Object.keys(content).length === 0) {
      results.push({
        httpStatusCode: Number(statusCode) as HttpStatusCode,
        name: null,
        description: response.description ?? "",
        links,
        body: null,
        headers,
        headerNames,
        schema: null,
        schemaRefName: null,
      });
      continue;
    }

    for (const [mimeType, mediaType] of Object.entries(content)) {
      const examples = mediaType.examples;
      const schema = mediaType.schema
        ? JSON.stringify(mediaType.schema, null, 2)
        : null;
      const refName = schemaRefName(rawResponseContent?.[mimeType]?.schema);

      if (examples) {
        for (const [, exampleOrRef] of Object.entries(examples)) {
          if (isReferenceObject(exampleOrRef)) {
            continue;
          }
          const example = exampleOrRef;
          const isJsonMimeType = mimeType.includes("json");
          results.push({
            httpStatusCode: Number(statusCode) as HttpStatusCode,
            name: (example["x-ember-nexus-name"] as string | undefined) ?? null,
            description: example.summary ?? response.description ?? "",
            links,
            body: {
              content:
                isJsonMimeType || typeof example.value !== "string"
                  ? JSON.stringify(example.value, null, 2)
                  : example.value,
              type: isJsonMimeType ? "json" : "plain",
            },
            headers,
            headerNames,
            schema,
            schemaRefName: refName,
          });
        }
      } else {
        // No example was provided. Distinguish a binary/file payload
        // (schema `format: "binary"`, e.g. an `application/octet-stream`
        // stream) from a genuinely empty body: the former has a body worth
        // announcing, it just isn't textual. Keyed on the schema `format`
        // rather than the mime type so it also catches binary `image/*`
        // etc.
        const schemaObject =
          mediaType.schema && !isReferenceObject(mediaType.schema)
            ? (mediaType.schema as SchemaObject)
            : undefined;
        const isBinary = schemaObject?.format === "binary";

        results.push({
          httpStatusCode: Number(statusCode) as HttpStatusCode,
          name: null,
          description: response.description ?? "",
          links,
          body: isBinary ? { type: "binary" } : null,
          headers,
          headerNames,
          schema,
          schemaRefName: refName,
        });
      }
    }
  }

  return results;
}

export function schemaParam(name: string): string {
  return name
    .replace(/([a-z0-9])([A-Z])/g, "$1-$2") // camelCase / PascalCase boundary
    .replace(/[^a-zA-Z0-9]+/g, "-") // any other separator → dash
    .toLowerCase()
    .replace(/^-+|-+$/g, ""); // trim leading/trailing dashes
}

export function extractSchemas(spec: OpenAPIObject): Schema[] {
  const schemas = spec.components?.schemas ?? {};
  return Object.entries(schemas).map(([name, schema]): Schema => ({
    id: schemaParam(name),
    name,
    schema,
  }));
}

export function extractSchema(
  spec: OpenAPIObject,
  name: string,
): Schema | undefined {
  const schema = spec.components?.schemas?.[name];
  if (schema === undefined) {
    return undefined;
  }
  return { id: schemaParam(name), name, schema };
}
