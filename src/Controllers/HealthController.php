<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

final class HealthController extends BaseController
{
    public function check(Request $request): Response
    {
        $checks = ['app' => true];
        try {
            $db = $this->app()->container->make(Database::class);
            $db->scalar('SELECT 1');
            $checks['database'] = true;
        } catch (\Throwable $e) {
            $checks['database'] = false;
            $checks['database_error'] = $e->getMessage();
        }
        $ok = !in_array(false, $checks, true);
        return $this->json(['status' => $ok ? 'ok' : 'degraded', 'checks' => $checks], $ok ? 200 : 503);
    }
}
