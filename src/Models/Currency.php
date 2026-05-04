<?php

declare(strict_types=1);

namespace App\Models;

final class Currency extends BaseModel
{
    protected static string $table = 'currencies';
    protected static string $primaryKey = 'code';
}
