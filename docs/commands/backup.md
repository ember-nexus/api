# Database

## Backup List

Lists all available backups. Backups are stored in `./var/backup/`.

```bash
php bin/console backup:list
```

![Screenshot of a terminal running the backup:list command](../assets/command-backup-list.png)

## Backup Create

Creates a new backup. Backups are stored in `./var/backup/`.

```bash
php bin/console backup:create
```

![Screenshot of a terminal running the backup:create command](../assets/command-backup-create.png)


## Backup Load

Loads a backup. Database must be empty.

```bash
php bin/console backup:load
```

![Screenshot of a terminal running the backup:load command](../assets/command-backup-load.png)
