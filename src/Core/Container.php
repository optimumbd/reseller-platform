<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Tiny PSR-11-ish DI container with auto-wiring.
 */
final class Container
{
    /** @var array<string,mixed> */
    private array $bindings = [];

    /** @var array<string,object> */
    private array $instances = [];

    public function bind(string $abstract, Closure|string $concrete = null, bool $shared = false): void
    {
        $this->bindings[$abstract] = ['concrete' => $concrete ?? $abstract, 'shared' => $shared];
    }

    public function singleton(string $abstract, Closure|string $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    public function instance(string $abstract, object $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]) || class_exists($abstract);
    }

    /**
     * @template T
     * @param  class-string<T>  $abstract
     * @return T
     */
    public function make(string $abstract): object
    {
        if (isset($this->instances[$abstract])) {
            /** @phpstan-ignore-next-line */
            return $this->instances[$abstract];
        }

        $binding = $this->bindings[$abstract] ?? null;
        $concrete = $binding['concrete'] ?? $abstract;

        $object = $concrete instanceof Closure
            ? $concrete($this)
            : $this->build($concrete);

        if (($binding['shared'] ?? false) === true) {
            $this->instances[$abstract] = $object;
        }

        /** @phpstan-ignore-next-line */
        return $object;
    }

    public function call(callable|array $callable, array $parameters = []): mixed
    {
        if (is_array($callable)) {
            [$class, $method] = $callable;
            $object = is_string($class) ? $this->make($class) : $class;
            $reflection = new \ReflectionMethod($object, $method);
            $args = $this->resolveArguments($reflection->getParameters(), $parameters);
            return $reflection->invokeArgs($object, $args);
        }
        $reflection = new \ReflectionFunction(\Closure::fromCallable($callable));
        $args = $this->resolveArguments($reflection->getParameters(), $parameters);
        return $reflection->invokeArgs($args);
    }

    private function build(string $concrete): object
    {
        $reflection = new ReflectionClass($concrete);
        if (!$reflection->isInstantiable()) {
            throw new \RuntimeException("Class {$concrete} is not instantiable.");
        }
        $ctor = $reflection->getConstructor();
        if ($ctor === null) {
            return new $concrete();
        }
        $args = $this->resolveArguments($ctor->getParameters(), []);
        return $reflection->newInstanceArgs($args);
    }

    /**
     * @param ReflectionParameter[] $parameters
     * @param array<string,mixed> $supplied
     * @return array<int,mixed>
     */
    private function resolveArguments(array $parameters, array $supplied): array
    {
        $args = [];
        foreach ($parameters as $param) {
            $name = $param->getName();
            if (array_key_exists($name, $supplied)) {
                $args[] = $supplied[$name];
                continue;
            }
            $type = $param->getType();
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $args[] = $this->make($type->getName());
                continue;
            }
            if ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
                continue;
            }
            if ($param->allowsNull()) {
                $args[] = null;
                continue;
            }
            throw new \RuntimeException("Cannot resolve dependency '{$name}'.");
        }
        return $args;
    }
}
