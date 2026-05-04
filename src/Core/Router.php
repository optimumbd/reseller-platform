<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Simple route registrar + dispatcher.
 *
 * Usage:
 *   $router->get('/foo/{id}', [SomeController::class, 'show'])->middleware('auth');
 *   $router->group(['prefix' => '/admin', 'middleware' => ['auth','admin']], function ($r) { ... });
 */
final class Router
{
    /** @var array<int,array{method:string,pattern:string,regex:string,params:string[],action:mixed,middleware:string[],name:?string}> */
    private array $routes = [];

    /** @var array<string,string> */
    private array $named = [];

    /** @var array{prefix:string,middleware:string[]} */
    private array $groupStack = ['prefix' => '', 'middleware' => []];

    public function __construct(private readonly App $app) {}

    public function get(string $pattern, mixed $action): RouteRegistrar
    {
        return $this->add('GET', $pattern, $action);
    }

    public function post(string $pattern, mixed $action): RouteRegistrar
    {
        return $this->add('POST', $pattern, $action);
    }

    public function put(string $pattern, mixed $action): RouteRegistrar
    {
        return $this->add('PUT', $pattern, $action);
    }

    public function patch(string $pattern, mixed $action): RouteRegistrar
    {
        return $this->add('PATCH', $pattern, $action);
    }

    public function delete(string $pattern, mixed $action): RouteRegistrar
    {
        return $this->add('DELETE', $pattern, $action);
    }

    public function any(string $pattern, mixed $action): RouteRegistrar
    {
        return $this->add('ANY', $pattern, $action);
    }

    public function group(array $attributes, callable $callback): void
    {
        $previous = $this->groupStack;
        $this->groupStack = [
            'prefix' => rtrim($previous['prefix'] . ($attributes['prefix'] ?? ''), '/'),
            'middleware' => array_merge($previous['middleware'], (array) ($attributes['middleware'] ?? [])),
        ];
        $callback($this);
        $this->groupStack = $previous;
    }

    public function name(string $name): self
    {
        // Apply name to last route registered.
        $i = count($this->routes) - 1;
        if ($i < 0) {
            return $this;
        }
        $this->routes[$i]['name'] = $name;
        $this->named[$name] = $this->routes[$i]['pattern'];
        return $this;
    }

    public function url(string $name, array $params = []): string
    {
        $pattern = $this->named[$name] ?? '/';
        foreach ($params as $key => $value) {
            $pattern = str_replace('{' . $key . '}', (string) $value, $pattern);
        }
        return $pattern;
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path = $request->path();

        foreach ($this->routes as $route) {
            if ($route['method'] !== 'ANY' && $route['method'] !== $method) {
                continue;
            }
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }
            $params = [];
            foreach ($route['params'] as $name) {
                $params[$name] = $matches[$name] ?? null;
            }
            $request->setRouteParams($params);
            return $this->runWithMiddleware($route, $request, $params);
        }

        return Response::view('errors/404', [], 404);
    }

    private function add(string $method, string $pattern, mixed $action): RouteRegistrar
    {
        $pattern = '/' . trim(($this->groupStack['prefix'] ?? '') . '/' . ltrim($pattern, '/'), '/');
        if ($pattern === '') {
            $pattern = '/';
        }
        $params = [];
        $regex = preg_replace_callback('/\{(\w+)(?::([^}]+))?\}/', function ($m) use (&$params) {
            $params[] = $m[1];
            $sub = $m[2] ?? '[^/]+';
            return '(?P<' . $m[1] . '>' . $sub . ')';
        }, $pattern);
        $regex = '#^' . $regex . '$#';

        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'regex' => $regex,
            'params' => $params,
            'action' => $action,
            'middleware' => $this->groupStack['middleware'] ?? [],
            'name' => null,
        ];
        return new RouteRegistrar($this, count($this->routes) - 1);
    }

    public function pushMiddleware(int $index, string $middleware): void
    {
        if (!isset($this->routes[$index])) {
            return;
        }
        $this->routes[$index]['middleware'][] = $middleware;
    }

    public function setRouteName(int $index, string $name): void
    {
        if (!isset($this->routes[$index])) {
            return;
        }
        $this->routes[$index]['name'] = $name;
        $this->named[$name] = $this->routes[$index]['pattern'];
    }

    private function runWithMiddleware(array $route, Request $request, array $params): Response
    {
        $action = $route['action'];
        $middleware = $route['middleware'];

        $core = function (Request $request) use ($action, $params): Response {
            $result = $this->callAction($action, $request, $params);
            return $result instanceof Response ? $result : Response::make((string) $result);
        };

        // Compose middleware (last-defined wraps innermost).
        $pipeline = array_reduce(
            array_reverse($middleware),
            function (\Closure $next, string $name): \Closure {
                return function (Request $request) use ($next, $name): Response {
                    $instance = $this->resolveMiddleware($name);
                    return $instance->handle($request, $next);
                };
            },
            $core
        );

        return $pipeline($request);
    }

    private function callAction(mixed $action, Request $request, array $params): mixed
    {
        if (is_callable($action)) {
            return $this->app->container->call($action, ['request' => $request] + $params);
        }
        if (is_array($action) && count($action) === 2) {
            [$class, $method] = $action;
            $instance = $this->app->container->make($class);
            return $this->app->container->call([$instance, $method], ['request' => $request] + $params);
        }
        if (is_string($action) && str_contains($action, '@')) {
            [$class, $method] = explode('@', $action, 2);
            $instance = $this->app->container->make($class);
            return $this->app->container->call([$instance, $method], ['request' => $request] + $params);
        }
        throw new \RuntimeException('Invalid route action.');
    }

    private function resolveMiddleware(string $name): object
    {
        $aliases = $this->app->config('app.middleware_aliases', []);
        $class = $aliases[$name] ?? $name;
        return $this->app->container->make($class);
    }
}

final class RouteRegistrar
{
    public function __construct(private readonly Router $router, private readonly int $index) {}

    public function middleware(string|array $middleware): self
    {
        foreach ((array) $middleware as $m) {
            $this->router->pushMiddleware($this->index, $m);
        }
        return $this;
    }

    public function name(string $name): self
    {
        $this->router->setRouteName($this->index, $name);
        return $this;
    }
}
