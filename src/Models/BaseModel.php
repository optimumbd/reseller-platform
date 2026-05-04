<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\App;
use App\Core\Database;
use App\Core\Pagination;

/**
 * Tiny ActiveRecord-style base class. Subclasses set $table and (optionally)
 * casts; everything else is generic.
 */
abstract class BaseModel
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';
    /** @var array<string,mixed> */
    public array $attributes = [];

    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    public function __get(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    public function __set(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }

    public function __isset(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    protected static function db(): Database
    {
        return App::getInstance()->db;
    }

    public static function tableName(): string
    {
        return static::$table;
    }

    public static function find(int|string $id): ?static
    {
        $row = self::db()->selectOne(
            sprintf('SELECT * FROM `%s` WHERE `%s` = :id LIMIT 1', static::$table, static::$primaryKey),
            ['id' => $id]
        );
        return $row ? new static($row) : null;
    }

    public static function where(string $whereSql, array $bindings = []): array
    {
        $rows = self::db()->select(
            sprintf('SELECT * FROM `%s` WHERE %s', static::$table, $whereSql),
            $bindings
        );
        return array_map(fn ($r) => new static($r), $rows);
    }

    public static function whereOne(string $whereSql, array $bindings = []): ?static
    {
        $row = self::db()->selectOne(
            sprintf('SELECT * FROM `%s` WHERE %s LIMIT 1', static::$table, $whereSql),
            $bindings
        );
        return $row ? new static($row) : null;
    }

    public static function all(string $orderBy = 'id ASC', int $limit = 1000): array
    {
        $rows = self::db()->select(
            sprintf('SELECT * FROM `%s` ORDER BY %s LIMIT %d', static::$table, $orderBy, $limit)
        );
        return array_map(fn ($r) => new static($r), $rows);
    }

    public static function count(string $whereSql = '1=1', array $bindings = []): int
    {
        $sql = sprintf('SELECT COUNT(*) FROM `%s` WHERE %s', static::$table, $whereSql);
        return (int) self::db()->scalar($sql, $bindings);
    }

    public static function paginate(string $whereSql, array $bindings, int $page, int $perPage = 20, string $orderBy = 'id DESC', string $baseUrl = ''): Pagination
    {
        $total = self::count($whereSql, $bindings);
        $offset = max(0, ($page - 1) * $perPage);
        $rows = self::db()->select(
            sprintf('SELECT * FROM `%s` WHERE %s ORDER BY %s LIMIT %d OFFSET %d', static::$table, $whereSql, $orderBy, $perPage, $offset),
            $bindings
        );
        $items = array_map(fn ($r) => new static($r), $rows);
        return new Pagination($total, $page, $perPage, $items, $baseUrl);
    }

    public function save(): bool
    {
        $pk = static::$primaryKey;
        if (!empty($this->attributes[$pk])) {
            $data = $this->attributes;
            unset($data[$pk]);
            self::db()->update(
                static::$table,
                $data,
                "`{$pk}` = :__pk",
                ['__pk' => $this->attributes[$pk]]
            );
            return true;
        }
        $id = self::db()->insert(static::$table, $this->attributes);
        $this->attributes[$pk] = (int) $id;
        return true;
    }

    public function delete(): bool
    {
        $pk = static::$primaryKey;
        if (empty($this->attributes[$pk])) {
            return false;
        }
        self::db()->delete(static::$table, "`{$pk}` = :id", ['id' => $this->attributes[$pk]]);
        return true;
    }

    public static function create(array $data): static
    {
        $instance = new static($data);
        $instance->save();
        return $instance;
    }

    public function toArray(): array
    {
        return $this->attributes;
    }
}
