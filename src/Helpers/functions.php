<?php

declare(strict_types=1);

use App\Core\App;
use App\Core\Response;
use App\Core\View;

// NOTE: Throughout this file, references to other application classes use
// fully-qualified names with a leading backslash (e.g. \App\Models\User)
// because the `use App\Core\App;` import above makes `App` an alias for
// `App\Core\App`, which would mis-resolve any unprefixed `App\...` reference.

if (!function_exists('app')) {
    function app(?string $abstract = null): mixed
    {
        $app = App::getInstance();
        if ($abstract === null) {
            return $app;
        }
        return $app->container->make($abstract);
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return App::getInstance()->config($key, $default);
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === null) {
            return $default;
        }
        return match (strtolower((string) $value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => $value,
        };
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        return App::getInstance()->basePath($path);
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        return App::getInstance()->storagePath($path);
    }
}

if (!function_exists('public_path')) {
    function public_path(string $path = ''): string
    {
        return App::getInstance()->publicPath($path);
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = rtrim((string) config('app.url', ''), '/');
        if ($base === '') {
            $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $base = "{$scheme}://{$host}";
        }
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('theme_asset')) {
    function theme_asset(string $path): string
    {
        $theme = (string) config('app.theme', 'default');
        return url("assets/themes/{$theme}/" . ltrim($path, '/'));
    }
}

if (!function_exists('route')) {
    function route(string $name, array $params = []): string
    {
        return App::getInstance()->router->url($name, $params);
    }
}

if (!function_exists('view')) {
    function view(string $template, array $data = []): Response
    {
        return Response::view($template, $data);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url, int $status = 302): Response
    {
        return Response::redirect($url, $status);
    }
}

if (!function_exists('back')) {
    function back(): Response
    {
        return Response::back();
    }
}

if (!function_exists('json_response')) {
    function json_response(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return App::getInstance()->session->csrfToken();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('method_field')) {
    function method_field(string $method): string
    {
        return '<input type="hidden" name="_method" value="' . e(strtoupper($method)) . '">';
    }
}

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = null): mixed
    {
        $session = App::getInstance()->session;
        $old = $session->getFlash('_old', []);
        // Re-flash so it survives subsequent partial renders during the request.
        $session->flash('_old', $old);
        return $old[$key] ?? $default;
    }
}

if (!function_exists('flash')) {
    function flash(string $key, mixed $value): void
    {
        App::getInstance()->session->flash($key, $value);
    }
}

if (!function_exists('flash_get')) {
    function flash_get(string $key, mixed $default = null): mixed
    {
        return App::getInstance()->session->getFlash($key, $default);
    }
}

if (!function_exists('auth_user')) {
    /**
     * @return array<string,mixed>|null
     */
    function auth_user(): ?array
    {
        return \App\Models\User::current();
    }
}

if (!function_exists('auth_check')) {
    function auth_check(): bool
    {
        return App::getInstance()->session->userId() !== null;
    }
}

if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        $user = auth_user();
        return $user !== null && in_array(($user['role'] ?? 'customer'), ['admin', 'moderator'], true);
    }
}

if (!function_exists('__')) {
    function __(string $key, array $replace = []): string
    {
        return \App\Services\TranslationService::translate($key, $replace);
    }
}

if (!function_exists('money')) {
    function money(float|int|string $amount, ?string $currency = null): string
    {
        $cur = $currency ?? (string) config('currency.default', 'USD');
        $amount = (float) $amount;
        return \App\Helpers\PriceHelper::format($amount, $cur);
    }
}

if (!function_exists('partial')) {
    function partial(string $template, array $data = []): string
    {
        return View::partial($template, $data);
    }
}

if (!function_exists('include_view')) {
    function include_view(string $template, array $data = []): string
    {
        return View::render($template, $data);
    }
}

if (!function_exists('layout')) {
    function layout(string $name = 'layouts/app', array $data = []): void
    {
        View::extend($name, $data);
    }
}

if (!function_exists('now')) {
    function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('uuid')) {
    function uuid(): string
    {
        return \App\Helpers\UuidHelper::v4();
    }
}

if (!function_exists('str_random')) {
    function str_random(int $length = 32): string
    {
        return bin2hex(random_bytes((int) ceil($length / 2)));
    }
}

if (!function_exists('current_locale')) {
    function current_locale(): string
    {
        return (string) (App::getInstance()->session->get('locale') ?? config('app.locale', 'en'));
    }
}

if (!function_exists('locale_set')) {
    function locale_set(string $locale): void
    {
        App::getInstance()->session->put('locale', $locale);
    }
}

if (!function_exists('current_currency')) {
    function current_currency(): string
    {
        return (string) (App::getInstance()->session->get('currency') ?? config('currency.default', 'USD'));
    }
}

if (!function_exists('dark_mode_class')) {
    function dark_mode_class(): string
    {
        $pref = (string) (App::getInstance()->session->get('theme_mode') ?? 'system');
        return match ($pref) {
            'dark' => 'dark',
            'light' => '',
            default => '', // 'system' — flipped via JS using prefers-color-scheme
        };
    }
}

if (!function_exists('config_setting')) {
    /**
     * Read a value from the `site_settings` table (cached).
     */
    function config_setting(string $key, mixed $default = null): mixed
    {
        return \App\Services\SettingService::get($key, $default);
    }
}
