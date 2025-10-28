<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait HandlesApiResponses
{
    /**
     * Return success response
     */
    protected function successResponse(string $message, $data = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    /**
     * Return error response
     */
    protected function errorResponse(string $message, int $status = 500, $errors = null): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'errors' => $errors
        ], $status);
    }

    /**
     * Return paginated response
     */
    protected function paginatedResponse(
        $data,
        int $page,
        int $perPage,
        int $total,
        string $message = 'Consulta exitosa'
    ): JsonResponse {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage),
            'total_items' => $total
        ]);
    }

    /**
     * Return validation error response
     */
    protected function validationErrorResponse(string $message, $errors = null): JsonResponse
    {
        return $this->errorResponse($message, 422, $errors);
    }

    /**
     * Return not found response
     */
    protected function notFoundResponse(string $message = 'Registro no encontrado'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }

    /**
     * Return unauthorized response
     */
    protected function unauthorizedResponse(string $message = 'No autorizado'): JsonResponse
    {
        return $this->errorResponse($message, 401);
    }

    /**
     * Return forbidden response
     */
    protected function forbiddenResponse(string $message = 'Prohibido'): JsonResponse
    {
        return $this->errorResponse($message, 403);
    }
}