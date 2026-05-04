<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Core\Http\HttpClientInterface;
use App\Core\Http\Response;

/**
 * Test double — records every request, returns canned responses by index, and
 * lets tests assert on the request shape (method, url, body, headers).
 *
 * Either preload a list of responses ($queue) or pass a callable that derives
 * the response from the request.
 *
 * @phpstan-type RecordedRequest array{method:string,url:string,body:mixed,headers:array<string,string>}
 */
final class InMemoryHttpClient implements HttpClientInterface
{
    /** @var list<RecordedRequest> */
    public array $requests = [];

    /** @var list<Response> */
    private array $queue;

    /** @var (callable(RecordedRequest):Response)|null */
    private $resolver;

    /**
     * @param Response|list<Response>|null $responses
     * @param (callable(array{method:string,url:string,body:mixed,headers:array<string,string>}):Response)|null $resolver
     */
    public function __construct(
        Response|array|null $responses = null,
        ?callable $resolver = null,
    ) {
        if ($responses === null) {
            $this->queue = [];
        } elseif ($responses instanceof Response) {
            $this->queue = [$responses];
        } else {
            $this->queue = array_values($responses);
        }
        $this->resolver = $resolver;
    }

    public function enqueue(Response $response): self
    {
        $this->queue[] = $response;
        return $this;
    }

    public function lastRequest(): ?array
    {
        return $this->requests === [] ? null : $this->requests[count($this->requests) - 1];
    }

    public function request(string $method, string $url, mixed $body = null, array $headers = []): Response
    {
        $entry = ['method' => $method, 'url' => $url, 'body' => $body, 'headers' => $headers];
        $this->requests[] = $entry;
        if ($this->resolver !== null) {
            return ($this->resolver)($entry);
        }
        if ($this->queue === []) {
            return new Response(200, [], '');
        }
        return array_shift($this->queue);
    }

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

    public static function jsonResponse(int $status, array $payload): Response
    {
        return new Response($status, ['content-type' => 'application/json'], json_encode($payload, JSON_UNESCAPED_SLASHES));
    }
}
