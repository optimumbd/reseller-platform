<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\App;
use App\Helpers\SecurityHelper;

final class User extends BaseModel
{
    protected static string $table = 'users';

    public static function findByEmail(string $email): ?self
    {
        return self::whereOne('email = :email', ['email' => $email]);
    }

    public static function current(): ?array
    {
        $id = App::getInstance()->session->userId();
        if ($id === null) {
            return null;
        }
        $row = App::getInstance()->db->selectOne('SELECT * FROM users WHERE id = :id LIMIT 1', ['id' => $id]);
        return $row ?: null;
    }

    public static function currentModel(): ?self
    {
        $id = App::getInstance()->session->userId();
        return $id ? self::find($id) : null;
    }

    public function isAdmin(): bool
    {
        return in_array($this->attributes['role'] ?? 'customer', ['admin', 'moderator'], true);
    }

    public function isReseller(): bool
    {
        return ($this->attributes['role'] ?? 'customer') === 'reseller';
    }

    public function setPassword(string $plain): void
    {
        $this->attributes['password'] = SecurityHelper::hashPassword($plain);
    }

    public function checkPassword(string $plain): bool
    {
        return SecurityHelper::verifyPassword($plain, (string) ($this->attributes['password'] ?? ''));
    }
}
