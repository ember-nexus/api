FROM alpine:3.24 AS core

RUN apk add nodejs npm pnpm git && \
    addgroup -S astro && adduser -S astro -G astro -u 1000 && \
    mkdir /core && \
    chmod 777 /core

FROM core AS development
RUN apk add bash

FROM core AS production-build
ARG VERSION=dev
WORKDIR /core
COPY docs .
ENV CI=true
ENV VERSION=${VERSION}
RUN pnpm install && pnpm run build

FROM caddy:2.11.4-alpine AS production
COPY --from=production-build /core/dist /srv
COPY ./docs-tools/Caddyfile /etc/caddy/Caddyfile
EXPOSE 80
