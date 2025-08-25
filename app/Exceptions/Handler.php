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
        // 401
        $this->renderable(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return $this->jsonError('No autenticado', 401, $e, [
                    'type'   => 'UNAUTHENTICATED',
                    'detail' => 'Debes iniciar sesión para acceder a este recurso.',
                ]);
            }
        });

        // 403
        $this->renderable(function (AuthorizationException $e, Request $request) {
            if ($request->is('api/*')) {
                return $this->jsonError('Acceso denegado', 403, $e, [
                    'type'   => 'FORBIDDEN',
                    'detail' => 'No cuentas con permisos suficientes para realizar esta acción.',
                ]);
            }
        });

        // 404 por modelo no encontrado
        $this->renderable(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                $model = class_basename($e->getModel());
                return $this->jsonError('Recurso no encontrado', 404, $e, [
                    'type'   => 'NOT_FOUND',
                    'detail' => "No se encontró el recurso solicitado ({$model}). Verifica el identificador.",
                ]);
            }
        });

        // 404 por ruta/recurso HTTP
        $this->renderable(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return $this->jsonError('Ruta o recurso no encontrado', 404, $e, [
                    'type'   => 'NOT_FOUND',
                    'detail' => 'Verifica la URL o el endpoint al que estás llamando.',
                ]);
            }
        });

        // 405
        $this->renderable(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                $allowHeader = $e->getHeaders()['Allow'] ?? '';
                $allowed = array_filter(array_map('trim', preg_split('/\s*,\s*/', (string) $allowHeader)));
                return $this->jsonError('Método HTTP no permitido', 405, $e, [
                    'type'            => 'METHOD_NOT_ALLOWED',
                    'detail'          => 'El método usado no está permitido para esta ruta.',
                    'allowed_methods' => $allowed,
                ]);
            }
        });

        // 422
        $this->renderable(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {

                // Estructura de errores por campo
                $errors = $e->errors();
                $fields = collect($errors)->map(function (array $messages, string $field) {
                    return [
                        'field'    => $field,
                        'messages' => array_values($messages),
                    ];
                })->values();

                // Detectar regla unique en 'email' para mensaje amigable
                $failed = method_exists($e, 'validator') && $e->validator ? $e->validator->failed() : [];
                $emailFailedRules = array_change_key_case($failed['email'] ?? [], CASE_LOWER);
                $duplicateEmail = array_key_exists('unique', $emailFailedRules);

                $title = $duplicateEmail
                    ? 'El usuario ya está registrado.'
                    : 'Datos inválidos. Corrige los campos marcados.';

                return $this->jsonError($title, 422, $e, [
                    'type'   => 'VALIDATION_ERROR',
                    'detail' => 'La información enviada no cumple con los requisitos de validación.',
                    'fields' => $fields,
                ]);
            }
        });

        // 429
        $this->renderable(function (ThrottleRequestsException $e, Request $request) {
            if ($request->is('api/*')) {
                $retryAfter = $e->getHeaders()['Retry-After'] ?? null;
                $retryAfter = is_numeric($retryAfter) ? (int) $retryAfter : null;

                return $this->jsonError('Demasiadas solicitudes', 429, $e, [
                    'type'                   => 'TOO_MANY_REQUESTS',
                    'detail'                 => 'Has alcanzado el límite de peticiones. Inténtalo de nuevo más tarde.',
                    'retry_after_seconds'    => $retryAfter,
                ]);
            }
        });

        // Fallback
        $this->renderable(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                $status  = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

                // Para 500 usamos un mensaje genérico y seguro
                $title   = $status === 500
                    ? 'Error interno del servidor'
                    : ($e->getMessage() ?: 'Error');

                $extra = [
                    'type'   => $status === 500 ? 'SERVER_ERROR' : 'ERROR',
                    'detail' => $status === 500
                        ? 'Hemos registrado el problema y trabajaremos para solucionarlo.'
                        : 'Ha ocurrido un error al procesar tu solicitud.',
                ];

                return $this->jsonError($title, $status, $e, $extra);
            }
        });
    }

    /**
     * Respuesta JSON estandarizada para errores.
     */
    private function jsonError(string $message, int $status, Throwable $e, array $extra = [])
    {
        // Estructura consistente para el frontend
        $payload = array_merge([
            'success' => false,
            'type'    => $extra['type']   ?? 'ERROR',
            'status'  => $status,
            'title'   => $message,          // título amigable
            'message' => $message,          // compatibilidad hacia atrás
        ], $extra);

        // Metadatos de depuración (solo en local / debug)
        if (config('app.debug')) {
            $payload['exception'] = class_basename($e);
            $payload['file']      = $e->getFile();
            $payload['line']      = $e->getLine();
            $payload['trace']     = collect($e->getTrace())->take(3); // corto y útil
        }

        // Log detallado (servidor)
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
