<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\Handler;
use Throwable;

/**
 * Application bootstrap + service container facade.
 */
final class App
{
    private static ?self $instance = null;

    public readonly Container $container;
    public readonly Router $router;
    public readonly Database $db;
    public readonly Session $session;
    public readonly Logger $logger;
    public readonly Cache $cache;
    public readonly EventDispatcher $events;

    /** @var array<string,mixed> */
    private array $config = [];

    private function __construct(public readonly string $basePath)
    {
        $this->container = new Container();
        $this->loadEnv();
        $this->loadConfig();
        $this->logger = new Logger($this->storagePath('logs/app.log'));
        $this->session = new Session($this->config('app.session_name', 'rp_session'));
        $this->db = new Database($this->config('database'));
        $this->cache = new Cache($this->storagePath('cache'));
        $this->events = new EventDispatcher();
        $this->router = new Router($this);

        $this->container->instance(self::class, $this);
        $this->container->instance(Container::class, $this->container);
        $this->container->instance(Database::class, $this->db);
        $this->container->instance(Session::class, $this->session);
        $this->container->instance(Logger::class, $this->logger);
        $this->container->instance(Cache::class, $this->cache);
        $this->container->instance(EventDispatcher::class, $this->events);

        $this->registerErrorHandler();
        $this->registerRoutes();
    }

    public static function boot(string $basePath): self
    {
        return self::$instance ??= new self($basePath);
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            throw new \RuntimeException('App not booted. Call App::boot() first.');
        }
        return self::$instance;
    }

    public function run(): void
    {
        $request = Request::capture();
        $response = $this->router->dispatch($request);
        $response->send();
    }

    public function config(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = $this->config;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }

    public function setConfig(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $ref = &$this->config;
        foreach ($segments as $i => $segment) {
            if ($i === count($segments) - 1) {
                $ref[$segment] = $value;
                return;
            }
            if (!isset($ref[$segment]) || !is_array($ref[$segment])) {
                $ref[$segment] = [];
            }
            $ref = &$ref[$segment];
        }
    }

    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
    }

    public function storagePath(string $path = ''): string
    {
        return $this->basePath('storage') . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
    }

    public function publicPath(string $path = ''): string
    {
        return $this->basePath('public') . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
    }

    public function configPath(string $path = ''): string
    {
        return $this->basePath('config') . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
    }

    public function templatesPath(string $path = ''): string
    {
        return $this->basePath('templates') . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
    }

    public function isInstalled(): bool
    {
        return file_exists($this->basePath('install.lock'));
    }

    public function isDebug(): bool
    {
        return (bool) $this->config('app.debug', false);
    }

    public function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);
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

    private function loadEnv(): void
    {
        $envFile = $this->basePath('.env');
        if (!file_exists($envFile) || !is_readable($envFile)) {
            return;
        }
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$name, $value] = array_map('trim', explode('=', $line, 2));
            if ($name === '') {
                continue;
            }
            // Strip surrounding quotes
            if (strlen($value) >= 2) {
                $first = $value[0];
                $last = $value[strlen($value) - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }
            if (!array_key_exists($name, $_ENV)) {
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
                if (function_exists('putenv')) {
                    @putenv($name . '=' . $value);
                }
            }
        }
    }

    private function loadConfig(): void
    {
        $configDir = $this->configPath();
        if (!is_dir($configDir)) {
            return;
        }
        foreach (glob($configDir . '/*.php') as $file) {
            $key = basename($file, '.php');
            $this->config[$key] = require $file;
        }
    }

    private function registerErrorHandler(): void
    {
        $handler = new Handler($this);
        set_error_handler([$handler, 'handleError']);
        set_exception_handler([$handler, 'handleException']);
        register_shutdown_function([$handler, 'handleShutdown']);
    }

    private function registerRoutes(): void
    {
        $routesFile = $this->basePath('src/routes.php');
        if (file_exists($routesFile)) {
            (function () use ($routesFile): void {
                $router = $this->router;
                require $routesFile;
            })->call($this);
        }
    }
}
