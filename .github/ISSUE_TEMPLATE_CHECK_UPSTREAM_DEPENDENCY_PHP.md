---
title: Upstream dependency got updated - PHP with version {{ env.VERSION }}
assignees: Syndesi
labels: Dependency Update
---

Open tasks:

- [ ] Compare Dockerfile of [latest PHP alpine CLI](https://github.com/docker-library/php/blob/master/8.5/alpine3.23/cli/Dockerfile) with `./docker/Dockerfile` on intermediate build `php_embed`.
- If there are updates:
  - [ ] Update local Dockerfile.
  - [ ] Update documentation by running `FIX_COMMAND_OUTPUT=1 BACKUP_FOLDER_CAN_BE_RESET=1 composer test:example-generation-command`.
  - [ ] Add changelog entry.
  - [ ] Release new patch or minor release, depending on PHP version upgrade.
