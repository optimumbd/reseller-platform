<?php

declare(strict_types=1);

namespace App\Core;

/**
 * HTTP response value object.
 */
final class Response
{
    public function __construct(
        public string $body = '',
        public int $status = 200,
        /** @var array<string,string> */
        public array $headers = [],
    ) {}

    public static function make(string $body = '', int $status = 200, array $headers = []): self
    {
        return new self($body, $status, $headers);
    }

    public static function json(mixed $data, int $status = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'application/json';
        return new self((string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $status, $headers);
    }

    public static function redirect(string $url, int $status = 302): self
    {
        return new self('', $status, ['Location' => $url]);
    }

    public static function back(): self
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        return self::redirect($referer);
    }

    public static function view(string $template, array $data = [], int $status = 200): self
    {
        $body = View::render($template, $data);
        return new self($body, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function withCookie(string $name, string $value, int $expiresInSeconds = 0, array $options = []): self
    {
        $opts = array_merge([
            'expires' => $expiresInSeconds > 0 ? time() + $expiresInSeconds : 0,
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ], $options);
        $serialized = $name . '=' . rawurlencode($value);
        foreach ($opts as $k => $v) {
            if ($v === false || $v === null) {
                continue;
            }
            if ($v === true) {
                $serialized .= '; ' . ucfirst($k);
            } else {
                $serialized .= '; ' . ucfirst($k) . '=' . $v;
            }
        }
        $existing = $this->headers['Set-Cookie'] ?? null;
        $this->headers['Set-Cookie'] = $existing ? $existing . "\r\nSet-Cookie: " . $serialized : $serialized;
        return $this;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value, true);
            }
        }
        echo $this->body;
    }
}
