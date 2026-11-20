<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/guarita_app/storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/guarita_app/vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/guarita_app/bootstrap/app.php';

$app->usePublicPath(__DIR__);
$app->handleRequest(Request::capture());
