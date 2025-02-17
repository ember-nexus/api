---
title: Upstream dependency got updated - Alpine with tag {{ env.TAG }}
assignees: Syndesi
labels: Dependency Update
---

Open tasks:

- [ ] Check if Dockerfile compiles with the newest Alpine version.
- If there are updates:
  - [ ] Update local Dockerfile.
  - [ ] Update documentation by running `FIX_COMMAND_OUTPUT=1 BACKUP_FOLDER_CAN_BE_RESET=1 composer test:example-generation-command`.
  - [ ] Add changelog entry.
  - [ ] Release new patch release.
- See also:
  - [Alpine release branches](https://www.alpinelinux.org/releases/).
  - [Alpine news archive](https://www.alpinelinux.org/posts/).
  - [Alpine on Docker Hub](https://hub.docker.com/_/alpine).
  - [Alpine branches on GitHub](https://github.com/alpinelinux/aports/branches).
