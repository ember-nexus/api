---
title: Upstream dependency got updated: Nginx Unit with tag {{ env.TAG }}
assignees: Syndesi
labels: update dependency
---

Open tasks:

- [ ] Compare Dockerfile of [latest Nginx Unit](https://github.com/nginx/unit/tree/master/pkg/docker) with 
      `./docker/Dockerfile` on intermediate build `nginx_unit_builder`.
- If there are updates:
  - [ ] Update local Dockerfile.
  - [ ] Add changelog entry.
  - [ ] Release new patch release.
