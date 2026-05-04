<?php

declare(strict_types=1);

namespace App\Models;

final class Service extends BaseModel
{
    protected static string $table = 'services';

    public const TYPES = ['domain','hosting','email','ssl','vps','dedicated','addon'];
    public const STATUSES = ['pending','provisioning','active','suspended','terminated','failed','cancelled'];
}
