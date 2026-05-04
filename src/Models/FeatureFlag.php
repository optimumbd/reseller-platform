<?php

declare(strict_types=1);

namespace App\Models;

final class FeatureFlag extends BaseModel
{
    protected static string $table = 'feature_flags';
    protected static string $primaryKey = 'key';
}
