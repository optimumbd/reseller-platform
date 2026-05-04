<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Session wrapper with flash messages and CSRF token support.
 */
final class Session
{
    private bool $started = false;

    public function __construct(private readonly string $name = 'rp_session') {}

    public function start(): void
    {
        if ($this->started || PHP_SAPI === 'cli') {
            return;
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;
            return;
        }
        session_name($this->name);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
        $this->started = true;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->start();
        return $_SESSION[$key] ?? $default;
    }

    public function put(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        $this->start();
        return isset($_SESSION[$key]);
    }

    public function forget(string $key): void
    {
        $this->start();
        unset($_SESSION[$key]);
    }

    public function flush(): void
    {
        $this->start();
        $_SESSION = [];
    }

    public function regenerate(): void
    {
        $this->start();
        session_regenerate_id(true);
    }

    public function destroy(): void
    {
        $this->start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
        $this->started = false;
    }

    public function flash(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION['_flash'][$key] = $value;
    }

    public function getFlash(string $key, mixed $default = null): mixed
    {
        $this->start();
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    public function csrfToken(): string
    {
        $this->start();
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    public function verifyCsrf(?string $token): bool
    {
        if ($token === null) {
            return false;
        }
        return hash_equals($this->csrfToken(), $token);
    }

    public function userId(): ?int
    {
        $id = $this->get('user_id');
        return $id === null ? null : (int) $id;
    }

    public function login(int $userId): void
    {
        $this->put('user_id', $userId);
        $this->regenerate();
    }

    public function logout(): void
    {
        $this->forget('user_id');
        $this->regenerate();
    }
}
