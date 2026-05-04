# Installation

## Prerequisites
- PHP 8.1 or newer
- MySQL 5.7+ or MariaDB 10.4+
- Apache + mod_rewrite, or Nginx, or any web server that can rewrite to `public/index.php`
- The PHP extensions: `pdo_mysql`, `mbstring`, `openssl`, `json`, `fileinfo`, `curl`

## Method A — Web installer (recommended for shared hosting)

1. Download the project (or upload all files via FTP/SFTP/cPanel File Manager).
2. Set the document root for your domain to the `public/` directory.
   - On cPanel: **Domains → Document Root → /home/<user>/<domain>/public**
   - On Plesk: **Hosting Settings → Document root → public**
   - If you can't change the docroot, the project's root `.htaccess` rewrites all traffic into `public/`.
3. Create a database and a user with full privileges.
4. Visit `https://yourdomain.com/install`.
5. Fill in the form. The installer:
   - Writes `.env`
   - Runs every migration in `database/migrations/`
   - Seeds default TLD pricing & site settings
   - Creates your admin user
   - Creates `install.lock`

## Method B — Command line (VPS, dedicated)

```bash
git clone https://github.com/optimumbd/reseller-platform.git
cd reseller-platform
cp .env.example .env
# Edit .env: DB_*, APP_URL, APP_NAME, …
php bin/console key:generate
php bin/console migrate
php bin/console seed
```

Point your web server at `public/index.php`. For a quick local test:

```bash
php -S 127.0.0.1:8000 -t public
```

## Setting permissions

The `storage/` and `public/uploads/` directories must be writable by PHP:

```bash
mkdir -p storage/{cache,logs,sessions,framework} public/uploads
chmod -R 775 storage public/uploads
# On shared hosting where you can't chmod:
chmod -R 0777 storage public/uploads
```

## Re-running the installer

Delete `install.lock` and visit `/install`.
