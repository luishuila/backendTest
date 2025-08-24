<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use Illuminate\Support\Facades\Log;

class Handler extends ExceptionHandler
{
    public function register(): void
    {
        $this->renderable(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return $this->jsonError('Unauthenticated', 401, $e);
            }
        });

        $this->renderable(function (AuthorizationException $e, Request $request) {
            if ($request->is('api/*')) {
                return $this->jsonError('Forbidden', 403, $e);
            }
        });

        $this->renderable(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                return $this->jsonError('Not Found', 404, $e);
            }
        });

        $this->renderable(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return $this->jsonError('Not Found', 404, $e);
            }
        });

        $this->renderable(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return $this->jsonError('Method Not Allowed', 405, $e, [
                    'allowed_methods' => $e->getHeaders()['Allow'] ?? [],
                ]);
            }
        });

        $this->renderable(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return $this->jsonError('Validation failed', 422, $e, [
                    'errors' => $e->errors(),
                ]);
            }
        });

        $this->renderable(function (ThrottleRequestsException $e, Request $request) {
            if ($request->is('api/*')) {
                return $this->jsonError('Too Many Requests', 429, $e);
            }
        });

     
        $this->renderable(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                $status  = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;
                $message = $status === 500 ? 'Server Error' : $e->getMessage();

                return $this->jsonError($message, $status, $e);
            }
        });
    }


    private function jsonError(string $message, int $status, Throwable $e, array $extra = [])
    {
        $payload = array_merge([
            'message' => $message,
        ], $extra);

        if (config('app.debug')) {
            $payload['exception'] = class_basename($e);
            $payload['file']      = $e->getFile();
            $payload['line']      = $e->getLine();
            $payload['trace']     = collect($e->getTrace())->take(3); 
        }


        Log::error("API Exception: {$message}", [
            'status'    => $status,
            'exception' => get_class($e),
            'message'   => $e->getMessage(),
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
            'trace'     => $e->getTraceAsString(),
        ]);

        return response()->json($payload, $status);
    }
}