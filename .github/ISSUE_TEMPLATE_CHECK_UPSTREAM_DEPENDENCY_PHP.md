---
title: Upstream dependency got updated - PHP with version {{ env.VERSION }}
assignees: Syndesi
labels: Dependency Update
---

Open tasks:

- [ ] Compare Dockerfile of [latest PHP alpine CLI](https://github.com/docker-library/php/tree/master/8.3/alpine3.19/cli) with `./docker/Dockerfile` on intermediate build `php_embed`.
- If there are updates:
  - [ ] Update local Dockerfile.
  - [ ] Add changelog entry.
  - [ ] Release new patch or minor release, depending on PHP version upgrade.
