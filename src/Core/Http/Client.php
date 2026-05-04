<?php

declare(strict_types=1);

namespace App\Core\Http;

/**
 * Thin curl-based HTTP client (no Guzzle dependency — runs on shared hosting).
 */
class Client implements HttpClientInterface
{
    public function __construct(private readonly int $timeout = 30) {}

    public function get(string $url, array $headers = [], array $query = []): Response
    {
        if ($query) {
            $sep = str_contains($url, '?') ? '&' : '?';
            $url .= $sep . http_build_query($query);
        }
        return $this->request('GET', $url, null, $headers);
    }

    public function post(string $url, mixed $body = null, array $headers = []): Response
    {
        return $this->request('POST', $url, $body, $headers);
    }

    public function put(string $url, mixed $body = null, array $headers = []): Response
    {
        return $this->request('PUT', $url, $body, $headers);
    }

    public function patch(string $url, mixed $body = null, array $headers = []): Response
    {
        return $this->request('PATCH', $url, $body, $headers);
    }

    public function delete(string $url, array $headers = []): Response
    {
        return $this->request('DELETE', $url, null, $headers);
    }

    public function request(string $method, string $url, mixed $body = null, array $headers = []): Response
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'ResellerPlatform/1.0',
        ]);
        $hdrs = [];
        $hasContentType = false;
        foreach ($headers as $k => $v) {
            $hdrs[] = $k . ': ' . $v;
            if (strtolower($k) === 'content-type') {
                $hasContentType = true;
            }
        }
        if ($body !== null && !is_string($body)) {
            if (!$hasContentType) {
                $hdrs[] = 'Content-Type: application/json';
            }
            $body = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        if ($hdrs) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $hdrs);
        }
        $raw = curl_exec($ch);
        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException("HTTP request failed: {$err}");
        }
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $rawHeaders = substr((string) $raw, 0, $headerSize);
        $rawBody = substr((string) $raw, $headerSize);
        curl_close($ch);
        return new Response($statusCode, $this->parseHeaders($rawHeaders), $rawBody);
    }

    private function parseHeaders(string $raw): array
    {
        $headers = [];
        foreach (preg_split("/\r?\n/", trim($raw)) as $line) {
            if (str_contains($line, ':')) {
                [$k, $v] = explode(':', $line, 2);
                $headers[strtolower(trim($k))] = trim($v);
            }
        }
        return $headers;
    }
}
