# Reseller Platform

A production-ready domain & hosting reseller platform written in pure PHP 8.1+ that runs on **shared hosting** (cPanel, Plesk, DirectAdmin, …) without special dependencies.

- **No build step** — Tailwind CSS via CDN
- **No Composer install at runtime** — vendor dir is committed
- **No Redis / Memcached** — file-driver cache, sessions, and queue
- **No Node / npm** — pure PHP + plain JavaScript
- **Mobile, tablet, desktop** responsive
- **Dark mode** with system preference + manual toggle
- **i18n**: English & Bangla (with auto Tiro Bangla font)

## Architecture overview

```
public/index.php  ──►  App\Core\App::boot()  ──►  Router  ──►  Middleware pipeline  ──►  Controller  ──►  View
                                                  │
                                                  ├── Models (PDO ActiveRecord)
                                                  ├── Services (registrar, payment, provisioning, …)
                                                  └── i18n / Cache / Queue / Mailer
```

Every product type — **domains, hosting, email, SSL, VPS, dedicated** — flows through one unified billing & provisioning pipeline:

```
Product ──► Cart ──► Order ──► Invoice ──► Payment ──► Service ──► ProvisioningJob ──► ServiceLifecycleManager
```

## Requirements

| Component | Minimum |
| --- | --- |
| PHP | 8.1+ |
| Database | MySQL 5.7+ / MariaDB 10.4+ |
| Web server | Apache w/ mod_rewrite (or Nginx with rewrite to `public/index.php`) |
| PHP extensions | pdo_mysql, mbstring, openssl, json, fileinfo, curl |
| Disk | 100 MB |

## Install (shared hosting)

1. Upload all files to your hosting account (the contents of this repo).
2. Make sure your domain's web root is the `public/` directory.
   - On cPanel: use **Domains → Document Root** → set to `/home/<user>/<domain>/public`.
   - If you cannot change the docroot, the included `.htaccess` at the project root will rewrite to `public/`.
3. Create a MySQL database and a user with full privileges on it.
4. Visit `https://yourdomain.com/install` in a browser.
5. Fill in the install form (database, site URL, admin user). The installer will:
   - Write `.env`
   - Run all migrations (`database/migrations/*.sql`)
   - Seed default TLD pricing & site settings
   - Create your admin user
   - Drop an `install.lock` file (delete it to re-run the wizard)
6. Visit `/login` and sign in.

## Install (CLI / VPS)

```bash
git clone https://github.com/optimumbd/reseller-platform.git
cd reseller-platform
cp .env.example .env
# edit .env to set DB_*, APP_URL, etc.
php bin/console key:generate
php bin/console migrate
php bin/console seed
```

Then point your web server at `public/index.php`.

## CLI commands

```
php bin/console list                List all commands
php bin/console migrate             Run pending migrations
php bin/console seed                Seed sample data
php bin/console queue:work          Process queued jobs
php bin/console schedule:run        Run scheduled tasks (cron)
php bin/console domain:sync         Sync domain expiry & status
php bin/console invoice:remind      Send payment reminders
php bin/console cache:clear         Clear file cache
php bin/console key:generate        Generate APP_KEY
php bin/console route:list          Print routes
```

## Cron

Add a single entry that fires every minute:

```
* * * * * cd /path/to/reseller-platform && php bin/console schedule:run >> storage/logs/cron.log 2>&1
```

The `schedule:run` command dispatches to the right tasks based on the current time.

## Configuration

All env vars are read from `.env` (parsed natively, no Dotenv lib required). See `.env.example` for the full list.

Per-feature config files live in `config/`:
- `app.php` — name, URL, locale, timezone, debug
- `database.php` — DB connection
- `mail.php` — mailer (PHP `mail`, SMTP, Postmark, SES, Sendgrid)
- `payments.php` — Stripe, PayPal, bKash, Nagad, SSLCommerz, Manual, Wallet
- `registrars.php` — Mock, Namecheap, Spaceship, Cloudflare, OpenSRS
- `currency.php`, `fonts.php`, `notifications.php`, …

## Theming

The view layer falls back: `themes/{active_theme}/templates/{name}.php` → `templates/{name}.php`. Override any template by creating it in your theme.

Tailwind CSS is loaded via CDN; dark mode uses Tailwind's `class` strategy. Toggle via:
- Header button (sun/moon icon)
- Or `GET /theme-mode/{light|dark|system}`

## i18n

Languages: `lang/en/`, `lang/bn/`. Each file is a flat array under a namespace:

```php
// lang/en/common.php
return ['save' => 'Save', 'cancel' => 'Cancel', ...];
```

Use anywhere with `__('common.save')`. Switch with `GET /locale/{en|bn}`.

## Deploying to production

See `docs/DEPLOY.md` for shared-hosting, cPanel, Plesk, DirectAdmin, Docker, and Nginx examples.

## Security

See `SECURITY.md` for the disclosure policy. Please do **not** open a public issue for security bugs.

## License

MIT — see `LICENSE`.
