# Cron / scheduled tasks

The platform uses a single master command to run scheduled tasks:

```
* * * * * cd /path/to/reseller-platform && php bin/console schedule:run >> storage/logs/cron.log 2>&1
```

`schedule:run` checks the current time and dispatches the right job:

- **Every 15 minutes** — `domain:sync` (sync expiry & status from registrar)
- **Daily at 02:00** — `invoice:remind` (send payment reminders)

If you prefer separate cron entries, remove the master line and add the individual ones from `crontab.example`.

## Queue worker

On shared hosting, run a short-lived worker every minute:

```
* * * * * cd /path/to/reseller-platform && timeout 55 php bin/console queue:work >> storage/logs/queue.log 2>&1
```

On a VPS, run it as a `systemd` service that auto-restarts:

```
[Unit]
Description=Reseller queue worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/reseller-platform
ExecStart=/usr/bin/php bin/console queue:work
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```
