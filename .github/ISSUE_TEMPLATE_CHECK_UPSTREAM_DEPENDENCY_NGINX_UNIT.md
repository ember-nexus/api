---
title: Upstream dependency got updated - Nginx Unit with tag {{ env.TAG }}
assignees: Syndesi
labels: Dependency Update
---

Open tasks:

- [ ] Compare Dockerfile of [latest Nginx Unit](https://github.com/nginx/unit/tree/master/pkg/docker) with `./docker/Dockerfile` on intermediate build `nginx_unit_builder`.
- If there are updates:
  - [ ] Update local Dockerfile.
  - [ ] Update documentation by running `FIX_COMMAND_OUTPUT=1 BACKUP_FOLDER_CAN_BE_RESET=1 composer test:example-generation-command`.
  - [ ] Add changelog entry.
  - [ ] Release new patch release.
