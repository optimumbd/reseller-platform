<?php

declare(strict_types=1);

return [
    'sentry_dsn' => env('SENTRY_DSN'),
    'datadog_api_key' => env('DATADOG_API_KEY'),
    'log_level' => env('LOG_LEVEL', 'info'),
    'json_logs' => filter_var(env('JSON_LOGS', false), FILTER_VALIDATE_BOOLEAN),
];
