<?php

declare(strict_types=1);

namespace App\Core\Http;

/**
 * Minimal HTTP client surface used by registrar / payment drivers.
 *
 * Lets tests inject a fake without depending on the curl-backed `Client`.
 */
interface HttpClientInterface
{
    public function request(string $method, string $url, mixed $body = null, array $headers = []): Response;

    public function get(string $url, array $headers = [], array $query = []): Response;

    public function post(string $url, mixed $body = null, array $headers = []): Response;

    public function put(string $url, mixed $body = null, array $headers = []): Response;

    public function patch(string $url, mixed $body = null, array $headers = []): Response;

    public function delete(string $url, array $headers = []): Response;
}
