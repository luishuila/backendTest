<?php

use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
   
        api: __DIR__.'/../routes/api.php',  
       
    )
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->prepend(HandleCors::class);
        $middleware->alias([
             'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
           'force.json' => ForceJsonResponse::class,
        ]);

        $middleware->group('api', [
            SubstituteBindings::class,
            ThrottleRequests::class,
        ]);


        $middleware->alias([
            'throttle' => ThrottleRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

    })->create();
