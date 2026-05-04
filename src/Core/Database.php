<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;

/**
 * Thin PDO wrapper: prepared statements, transactions, simple query helpers.
 */
final class Database
{
    private ?PDO $pdo = null;

    /**
     * @param array{driver?:string,host?:string,port?:int|string,name?:string,user?:string,pass?:string,charset?:string,options?:array} $config
     */
    public function __construct(private readonly array $config) {}

    public function pdo(): PDO
    {
        if ($this->pdo !== null) {
            return $this->pdo;
        }
        $driver = $this->config['driver'] ?? 'mysql';
        $host = $this->config['host'] ?? '127.0.0.1';
        $port = $this->config['port'] ?? 3306;
        $name = $this->config['name'] ?? '';
        $user = $this->config['user'] ?? 'root';
        $pass = $this->config['pass'] ?? '';
        $charset = $this->config['charset'] ?? 'utf8mb4';

        $dsn = "{$driver}:host={$host};port={$port};dbname={$name};charset={$charset}";
        $options = ($this->config['options'] ?? []) + [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE {$charset}_unicode_ci",
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }
        return $this->pdo;
    }

    public function prepare(string $sql): PDOStatement
    {
        return $this->pdo()->prepare($sql);
    }

    /**
     * @param array<string,mixed>|array<int,mixed> $bindings
     */
    public function query(string $sql, array $bindings = []): PDOStatement
    {
        $stmt = $this->prepare($sql);
        $stmt->execute($bindings);
        return $stmt;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function select(string $sql, array $bindings = []): array
    {
        return $this->query($sql, $bindings)->fetchAll();
    }

    /**
     * @return array<string,mixed>|null
     */
    public function selectOne(string $sql, array $bindings = []): ?array
    {
        $row = $this->query($sql, $bindings)->fetch();
        return $row === false ? null : $row;
    }

    public function scalar(string $sql, array $bindings = []): mixed
    {
        $row = $this->query($sql, $bindings)->fetch(PDO::FETCH_NUM);
        return $row === false ? null : ($row[0] ?? null);
    }

    public function execute(string $sql, array $bindings = []): int
    {
        return $this->query($sql, $bindings)->rowCount();
    }

    public function insert(string $table, array $data): string
    {
        $cols = array_keys($data);
        $placeholders = array_map(fn ($c) => ':' . $c, $cols);
        $sql = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES (%s)',
            $table,
            implode('`, `', $cols),
            implode(', ', $placeholders)
        );
        $this->query($sql, $data);
        return $this->pdo()->lastInsertId();
    }

    public function update(string $table, array $data, string $whereSql, array $whereBindings = []): int
    {
        $set = [];
        $bindings = [];
        foreach ($data as $k => $v) {
            $set[] = "`{$k}` = :set_{$k}";
            $bindings["set_{$k}"] = $v;
        }
        $sql = sprintf('UPDATE `%s` SET %s WHERE %s', $table, implode(', ', $set), $whereSql);
        return $this->execute($sql, $bindings + $whereBindings);
    }

    public function delete(string $table, string $whereSql, array $whereBindings = []): int
    {
        return $this->execute("DELETE FROM `{$table}` WHERE {$whereSql}", $whereBindings);
    }

    public function transaction(callable $callback): mixed
    {
        $pdo = $this->pdo();
        $pdo->beginTransaction();
        try {
            $result = $callback($this);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function lastInsertId(): string
    {
        return $this->pdo()->lastInsertId();
    }
}
