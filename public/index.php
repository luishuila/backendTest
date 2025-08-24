<?php

declare(strict_types=1);

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

require __DIR__ . '/../vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require __DIR__ . '/../bootstrap/app.php';

/*
|--------------------------------------------------------------------------
| Run The Application (Laravel 11)
|--------------------------------------------------------------------------
| En Laravel 11 el Application maneja la solicitud directamente.
| No declares $request; pásalo inline para evitar advertencias del linter.
*/
$app->handleRequest(
    Request::capture()
);
