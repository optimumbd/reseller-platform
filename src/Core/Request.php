<?php

declare(strict_types=1);

namespace App\Core;

/**
 * HTTP request value object.
 */
final class Request
{
    /** @var array<string,mixed> */
    private array $routeParams = [];

    public function __construct(
        public readonly string $method,
        public readonly string $uri,
        /** @var array<string,mixed> */
        public readonly array $query,
        /** @var array<string,mixed> */
        public readonly array $body,
        /** @var array<string,mixed> */
        public readonly array $cookies,
        /** @var array<string,array> */
        public readonly array $files,
        /** @var array<string,string> */
        public readonly array $headers,
        public readonly string $ip,
        public readonly string $userAgent,
    ) {}

    public static function capture(): self
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $body = $_POST;
        if (!empty($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json')) {
            $raw = file_get_contents('php://input') ?: '{}';
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $body = $decoded;
            }
        }
        return new self(
            method: strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            uri: $_SERVER['REQUEST_URI'] ?? '/',
            query: $_GET,
            body: $body,
            cookies: $_COOKIE,
            files: $_FILES,
            headers: array_change_key_case($headers, CASE_LOWER),
            ip: self::resolveIp(),
            userAgent: $_SERVER['HTTP_USER_AGENT'] ?? '',
        );
    }

    public function method(): string
    {
        $override = $this->body['_method'] ?? $this->headers['x-http-method-override'] ?? null;
        if ($this->method === 'POST' && is_string($override)) {
            return strtoupper($override);
        }
        return $this->method;
    }

    public function path(): string
    {
        $path = parse_url($this->uri, PHP_URL_PATH) ?: '/';
        return '/' . trim($path, '/') === '/' ? '/' : '/' . trim($path, '/');
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function only(string ...$keys): array
    {
        $all = $this->all();
        return array_intersect_key($all, array_flip($keys));
    }

    public function header(string $name, ?string $default = null): ?string
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    public function bearerToken(): ?string
    {
        $auth = $this->header('authorization');
        if ($auth && preg_match('/Bearer\s+(.+)/i', $auth, $m)) {
            return $m[1];
        }
        return null;
    }

    public function isAjax(): bool
    {
        return strtolower($this->header('x-requested-with', '') ?? '') === 'xmlhttprequest';
    }

    public function expectsJson(): bool
    {
        $accept = $this->header('accept', '') ?? '';
        return str_contains($accept, 'application/json') || $this->isAjax();
    }

    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function route(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        return $file;
    }

    private static function resolveIp(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = explode(',', $_SERVER[$key])[0];
                return trim($ip);
            }
        }
        return '0.0.0.0';
    }
}
