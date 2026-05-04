<?php

declare(strict_types=1);

/*
 |--------------------------------------------------------------------------
 | Front Controller
 |--------------------------------------------------------------------------
 | Every HTTP request is funneled here via .htaccess. We bootstrap the
 | application, then dispatch the router.
 */

define('BASE_PATH', dirname(__DIR__));
define('APP_START', microtime(true));

require BASE_PATH . '/vendor/autoload.php';

use App\Core\App;

$app = App::boot(BASE_PATH);
$app->run();
