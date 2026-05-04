# Deploying

## cPanel / Plesk / DirectAdmin (shared hosting)

1. Upload the entire project to the user's home, e.g. `/home/<user>/reseller-platform/`.
2. Create a MySQL database and user from the panel.
3. Set the domain's docroot to `/home/<user>/reseller-platform/public`.
4. Visit `https://yourdomain.com/install` and complete the wizard.
5. Add a cron entry from the panel's *Cron Jobs* page:
   ```
   * * * * * cd /home/<user>/reseller-platform && php bin/console schedule:run >> storage/logs/cron.log 2>&1
   ```

If you cannot move the docroot:
- Keep all files inside `public_html/`.
- The included root `.htaccess` rewrites everything into `public/`.

## Apache (VPS, full root)

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/reseller-platform/public

    <Directory /var/www/reseller-platform/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/reseller_error.log
    CustomLog ${APACHE_LOG_DIR}/reseller_access.log combined
</VirtualHost>
```

Enable `mod_rewrite` and reload.

## Nginx

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/reseller-platform/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

## Updating

```bash
cd /path/to/reseller-platform
git pull
php bin/console migrate
php bin/console cache:clear
```

## SSL

Use the panel's Let's Encrypt integration, or `certbot` on a VPS. The app itself does not need any SSL config — it respects `HTTPS=on`.
