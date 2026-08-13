<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Trait ApiResponse
 *
 * Proporciona métodos para generar respuestas JSON consistentes
 * en todos los controladores de la API de Rentame.
 *
 * Formato de respuesta exitosa:
 * { "success": true, "data": {}, "message": "OK", "meta": {} }
 *
 * Formato de error:
 * { "success": false, "message": "...", "errors": {} }
 */
trait ApiResponse
{
    /**
     * Respuesta exitosa estándar.
     *
     * @param  mixed       $data    Datos a retornar
     * @param  string      $message Mensaje descriptivo
     * @param  int         $status  Código HTTP (default: 200)
     * @param  array       $meta    Metadatos adicionales (paginación, etc.)
     */
    protected function success(
        mixed $data = null,
        string $message = 'OK',
        int $status = 200,
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ];

        if (! empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $status);
    }

    /**
     * Respuesta de recurso creado (201 Created).
     *
     * @param  mixed  $data    Recurso creado
     * @param  string $message Mensaje descriptivo
     */
    protected function created(mixed $data = null, string $message = 'Creado exitosamente'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    /**
     * Respuesta sin contenido (204 No Content).
     */
    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Respuesta de error estándar.
     *
     * @param  string $message Mensaje de error legible
     * @param  int    $status  Código HTTP (default: 400)
     * @param  array  $errors  Errores de validación por campo
     */
    protected function error(
        string $message = 'Error',
        int $status = 400,
        array $errors = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (! empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    /**
     * Respuesta 401 Unauthorized.
     */
    protected function unauthorized(string $message = 'No autenticado'): JsonResponse
    {
        return $this->error($message, 401);
    }

    /**
     * Respuesta 403 Forbidden.
     */
    protected function forbidden(string $message = 'Sin permisos para esta acción'): JsonResponse
    {
        return $this->error($message, 403);
    }

    /**
     * Respuesta 404 Not Found.
     */
    protected function notFound(string $message = 'Recurso no encontrado'): JsonResponse
    {
        return $this->error($message, 404);
    }

    /**
     * Respuesta 422 Unprocessable Entity (errores de validación).
     */
    protected function validationError(array $errors, string $message = 'Error de validación'): JsonResponse
    {
        return $this->error($message, 422, $errors);
    }

    /**
     * Respuesta 429 Too Many Requests.
     */
    protected function tooManyRequests(string $message = 'Demasiadas solicitudes'): JsonResponse
    {
        return $this->error($message, 429);
    }

    /**
     * Respuesta con colección paginada.
     * Extrae metadatos de paginación de LengthAwarePaginator.
     *
     * @param  \Illuminate\Contracts\Pagination\LengthAwarePaginator $paginator
     * @param  mixed  $data    Datos ya transformados
     * @param  string $message Mensaje
     */
    protected function paginated(
        mixed $paginator,
        mixed $data,
        string $message = 'OK'
    ): JsonResponse {
        return $this->success($data, $message, 200, [
            'current_page' => $paginator->currentPage(),
            'per_page'     => $paginator->perPage(),
            'total'        => $paginator->total(),
            'last_page'    => $paginator->lastPage(),
            'from'         => $paginator->firstItem(),
            'to'           => $paginator->lastItem(),
        ]);
    }
}
