<?php

declare(strict_types=1);

namespace App\Services\Registrar;

use App\Services\Registrar\Contracts\RegistrarInterface;
use App\Services\Registrar\Drivers\MockRegistrar;

final class RegistrarFactory
{
    public static function default(): RegistrarInterface
    {
        $driver = (string) (config('registrars.default') ?? 'mock');
        return self::make($driver);
    }

    public static function make(string $driver): RegistrarInterface
    {
        return match ($driver) {
            'namecheap' => self::tryDriver('App\\Services\\Registrar\\Drivers\\NamecheapRegistrar'),
            'cloudflare' => self::tryDriver('App\\Services\\Registrar\\Drivers\\CloudflareRegistrar'),
            'spaceship' => self::tryDriver('App\\Services\\Registrar\\Drivers\\SpaceshipRegistrar'),
            'opensrs' => self::tryDriver('App\\Services\\Registrar\\Drivers\\OpensrsRegistrar'),
            default => new MockRegistrar(),
        };
    }

    private static function tryDriver(string $class): RegistrarInterface
    {
        if (class_exists($class) && is_subclass_of($class, RegistrarInterface::class)) {
            return new $class();
        }
        return new MockRegistrar();
    }
}
