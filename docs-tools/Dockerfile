FROM alpine:3.24 AS docs

RUN apk add nodejs npm pnpm git && \
    addgroup -S astro && adduser -S astro -G astro -u 1000 && \
    mkdir /docs && \
    chmod 777 /docs

FROM docs AS development
RUN apk add bash

FROM docs AS production-build
ARG VERSION=dev
WORKDIR /docs
COPY docs .
ENV CI=true
ENV VERSION=${VERSION}
RUN pnpm install && pnpm run build

FROM caddy:2.11.4-alpine AS production
COPY --from=production-build /docs/dist /srv
COPY ./docs-tools/Caddyfile /etc/caddy/Caddyfile
EXPOSE 80
