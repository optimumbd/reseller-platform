<?php

declare(strict_types=1);

namespace App\Exceptions;

class RateLimitException extends HttpException
{
    public function __construct(string $message = 'Too many requests')
    {
        parent::__construct(429, $message);
    }
}
