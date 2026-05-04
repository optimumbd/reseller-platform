<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Core\App;
use App\Core\Response;
use ErrorException;
use Throwable;

final class Handler
{
    public function __construct(private readonly App $app) {}

    public function handleError(int $errno, string $errstr, string $errfile = '', int $errline = 0): bool
    {
        if (!(error_reporting() & $errno)) {
            return false;
        }
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    public function handleException(Throwable $e): void
    {
        $this->app->logger->error($e->getMessage(), [
            'class' => $e::class,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $this->app->isDebug() ? $e->getTraceAsString() : null,
        ]);

        $request = null;
        try {
            $request = \App\Core\Request::capture();
        } catch (\Throwable) {
            // ignore
        }
        $expectsJson = $request?->expectsJson() ?? false;

        if ($e instanceof HttpException) {
            $status = $e->getStatusCode();
        } elseif ($e instanceof ValidationException) {
            $status = 422;
        } else {
            $status = 500;
        }

        if ($expectsJson) {
            Response::json(
                $this->app->isDebug()
                    ? ['error' => $e->getMessage(), 'class' => $e::class, 'trace' => $e->getTraceAsString()]
                    : ['error' => $status === 500 ? 'Server error' : $e->getMessage()],
                $status
            )->send();
            return;
        }

        try {
            Response::view('errors/' . $status, ['exception' => $e, 'debug' => $this->app->isDebug()], $status)->send();
        } catch (\Throwable) {
            http_response_code($status);
            echo $this->app->isDebug()
                ? '<h1>' . $status . ' — ' . get_class($e) . '</h1><pre>' . htmlspecialchars($e->getMessage() . "\n\n" . $e->getTraceAsString()) . '</pre>'
                : '<h1>' . $status . '</h1>';
        }
    }

    public function handleShutdown(): void
    {
        $err = error_get_last();
        if ($err !== null && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            $this->handleException(new ErrorException($err['message'], 0, $err['type'], $err['file'], $err['line']));
        }
    }
}
