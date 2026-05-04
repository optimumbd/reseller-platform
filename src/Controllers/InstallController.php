<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Encryption;
use App\Core\Migrator;
use App\Core\Request;
use App\Core\Response;
use App\Helpers\SecurityHelper;
use PDO;

final class InstallController extends BaseController
{
    public function show(Request $request): Response
    {
        if ($this->app()->isInstalled()) {
            return $this->redirect('/');
        }
        return $this->view('install/show', [
            'requirements' => $this->checkRequirements(),
        ]);
    }

    public function run(Request $request): Response
    {
        if ($this->app()->isInstalled()) {
            return $this->redirect('/');
        }

        $data = $request->all();
        $errors = $this->validate($data);
        if (!empty($errors)) {
            return $this->view('install/show', [
                'requirements' => $this->checkRequirements(),
                'errors' => $errors,
                'old' => $data,
            ]);
        }

        // Try connecting to DB to verify creds
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                $data['db_host'],
                (int) $data['db_port'],
                $data['db_name']
            );
            $pdo = new PDO($dsn, $data['db_user'], $data['db_pass'] ?? '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (\Throwable $e) {
            return $this->view('install/show', [
                'requirements' => $this->checkRequirements(),
                'errors' => ['db_connection' => 'Database connection failed: ' . $e->getMessage()],
                'old' => $data,
            ]);
        }

        // Write .env
        $appKey = Encryption::generateKey();
        $envContent = $this->buildEnv($data, $appKey);
        $envPath = $this->app()->basePath('.env');
        if (file_put_contents($envPath, $envContent) === false) {
            return $this->view('install/show', [
                'requirements' => $this->checkRequirements(),
                'errors' => ['env_write' => 'Could not write .env file. Check permissions.'],
                'old' => $data,
            ]);
        }

        // Run migrations on the freshly configured DB.
        // NOTE: MySQL DDL (CREATE TABLE etc.) implicitly commits, so wrapping
        // these in an explicit transaction is meaningless and ends with
        // "no active transaction" when commit() runs. Just exec sequentially.
        try {
            foreach ($this->loadAllSqlStatements() as $sql) {
                $pdo->exec($sql);
            }
        } catch (\Throwable $e) {
            return $this->view('install/show', [
                'requirements' => $this->checkRequirements(),
                'errors' => ['migrate' => 'Migration failed: ' . $e->getMessage()],
                'old' => $data,
            ]);
        }

        // Create admin
        $hash = SecurityHelper::hashPassword($data['admin_password']);
        $pdo->prepare(
            'INSERT INTO users (name, email, password, role, status, email_verified_at, created_at) '
            . 'VALUES (:n, :e, :p, "admin", "active", NOW(), NOW())'
        )->execute([
            'n' => $data['admin_name'],
            'e' => $data['admin_email'],
            'p' => $hash,
        ]);

        // Seed baseline data (TLD pricing, site_settings, hero section) using
        // the local PDO. Best-effort: errors here are non-fatal because the
        // app is already runnable — the admin can re-run seeders via CLI.
        try {
            $this->seedBaseline($pdo, $data);
        } catch (\Throwable) {
            // ignore — seeders are non-fatal
        }

        // Lock install
        file_put_contents($this->app()->basePath('install.lock'), date('c'));

        return $this->redirect('/install/complete');
    }

    public function complete(Request $request): Response
    {
        return $this->view('install/complete', []);
    }

    private function checkRequirements(): array
    {
        $required = [
            'PHP >= 8.1' => version_compare(PHP_VERSION, '8.1.0', '>='),
            'PDO MySQL' => extension_loaded('pdo_mysql'),
            'OpenSSL' => extension_loaded('openssl'),
            'Mbstring' => extension_loaded('mbstring'),
            'JSON' => extension_loaded('json'),
            'cURL' => extension_loaded('curl'),
            'storage/ writable' => is_writable($this->app()->storagePath()),
            'config writable' => is_writable($this->app()->basePath()),
        ];
        return $required;
    }

    private function validate(array $data): array
    {
        $errors = [];
        $required = ['db_host', 'db_port', 'db_name', 'db_user', 'admin_name', 'admin_email', 'admin_password'];
        foreach ($required as $f) {
            if (empty($data[$f])) {
                $errors[$f] = 'Required.';
            }
        }
        if (!empty($data['admin_email']) && !filter_var($data['admin_email'], FILTER_VALIDATE_EMAIL)) {
            $errors['admin_email'] = 'Invalid email.';
        }
        if (!empty($data['admin_password']) && strlen($data['admin_password']) < 8) {
            $errors['admin_password'] = 'Password must be at least 8 characters.';
        }
        return $errors;
    }

    private function buildEnv(array $data, string $appKey): string
    {
        $appUrl = rtrim($data['app_url'] ?? ('https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')), '/');
        return implode("\n", [
            'APP_NAME="' . str_replace('"', '\"', $data['app_name'] ?? 'Reseller Platform') . '"',
            'APP_ENV=production',
            'APP_DEBUG=false',
            'APP_URL=' . $appUrl,
            'APP_KEY=' . $appKey,
            'APP_LOCALE=' . ($data['locale'] ?? 'en'),
            'APP_TIMEZONE=' . ($data['timezone'] ?? 'UTC'),
            '',
            'DB_HOST=' . $data['db_host'],
            'DB_PORT=' . $data['db_port'],
            'DB_NAME=' . $data['db_name'],
            'DB_USER=' . $data['db_user'],
            'DB_PASS="' . str_replace('"', '\"', $data['db_pass'] ?? '') . '"',
            '',
            'MAIL_DRIVER=mail',
            'MAIL_FROM_ADDRESS=' . $data['admin_email'],
            'MAIL_FROM_NAME="' . str_replace('"', '\"', $data['app_name'] ?? 'Reseller Platform') . '"',
            '',
        ]) . "\n";
    }

    private function seedBaseline(PDO $pdo, array $data): void
    {
        // TLD pricing
        $existing = (int) $pdo->query('SELECT COUNT(*) FROM tld_pricing')->fetchColumn();
        if ($existing === 0) {
            $tlds = [
                ['com', 9.99, 12.99, 9.99],   ['net', 11.99, 13.99, 11.99],
                ['org', 10.99, 12.99, 10.99], ['io',  39.99, 42.99, 39.99],
                ['dev', 14.99, 16.99, 14.99], ['app', 16.99, 18.99, 16.99],
                ['xyz',  1.99,  9.99,  1.99], ['info', 3.99, 19.99,  3.99],
                ['biz',  4.99, 14.99,  4.99],
                ['com.bd', 1500.00, 1500.00, 1500.00],
                ['bd',     2500.00, 2500.00, 2500.00],
            ];
            $stmt = $pdo->prepare(
                'INSERT INTO tld_pricing (tld, register_price, renew_price, transfer_price, currency, is_active, sort_order) '
                . 'VALUES (:tld, :reg, :ren, :tra, :cur, 1, :sort)'
            );
            $sort = 1;
            foreach ($tlds as [$tld, $reg, $ren, $tra]) {
                $stmt->execute([
                    'tld' => $tld, 'reg' => $reg, 'ren' => $ren, 'tra' => $tra,
                    'cur' => str_ends_with($tld, 'bd') ? 'BDT' : 'USD',
                    'sort' => $sort++,
                ]);
            }
        }

        // Site settings
        $defaults = [
            'site_name' => $data['app_name'] ?? 'Reseller Platform',
            'support_email' => $data['admin_email'] ?? 'support@example.com',
            'default_currency' => 'USD',
            'default_locale' => 'en',
            'tax_rate' => '0',
            'company_address' => '',
        ];
        $sel = $pdo->prepare('SELECT `key` FROM site_settings WHERE `key` = ?');
        $ins = $pdo->prepare('INSERT INTO site_settings (`key`, `value`) VALUES (?, ?)');
        foreach ($defaults as $k => $v) {
            $sel->execute([$k]);
            if (!$sel->fetchColumn()) {
                $ins->execute([$k, (string) $v]);
            }
        }

        // Homepage hero section
        $sectionCount = (int) $pdo->query('SELECT COUNT(*) FROM homepage_sections')->fetchColumn();
        if ($sectionCount === 0) {
            $pdo->prepare(
                'INSERT INTO homepage_sections (`key`, title, content, is_active, sort_order) '
                . 'VALUES (?, ?, ?, 1, 1)'
            )->execute([
                'hero',
                'Find your perfect domain',
                json_encode([
                    'subtitle' => 'Register, transfer, and manage domains with ease.',
                    'cta_label' => 'Search domains',
                    'cta_href' => '/domains/search',
                ], JSON_UNESCAPED_UNICODE),
            ]);
        }
    }

    private function loadAllSqlStatements(): array
    {
        $migDir = $this->app()->basePath('database/migrations');
        $files = glob($migDir . '/*.sql') ?: [];
        sort($files);
        $stmts = [];
        foreach ($files as $f) {
            $contents = (string) file_get_contents($f);
            $contents = preg_replace('/--.*$/m', '', $contents) ?? '';
            foreach (explode(';', $contents) as $sql) {
                $sql = trim($sql);
                if ($sql !== '') {
                    $stmts[] = $sql;
                }
            }
        }
        return $stmts;
    }
}
