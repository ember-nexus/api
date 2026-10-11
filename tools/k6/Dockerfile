FROM grafana/k6:latest AS k6

FROM node:22-alpine

RUN apk add --no-cache pnpm

COPY --from=k6 /usr/bin/k6 /usr/local/bin/k6

WORKDIR /app

CMD ["sleep", "infinity"]
