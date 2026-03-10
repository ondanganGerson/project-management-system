<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Return a 200 success response.
     */
    protected function successResponse(mixed $data = null, string $message = 'Operation successful', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
        ], $statusCode);
    }

    /**
     * Return a 201 created response.
     */
    protected function createdResponse(mixed $data = null, string $message = 'Resource created successfully'): JsonResponse
    {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * Return a 204 no content response.
     */
    protected function noContentResponse(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Return an error response.
     */
    protected function errorResponse(string $message = 'An error occurred', int $statusCode = 400, mixed $errors = null): JsonResponse
    {
        $payload = [
            'status'  => 'error',
            'message' => $message,
            'data'    => null,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $statusCode);
    }

    /**
     * Return a 401 unauthorized response.
     */
    protected function unauthorizedResponse(string $message = 'Unauthorized. Please log in.'): JsonResponse
    {
        return $this->errorResponse($message, 401);
    }

    /**
     * Return a 403 forbidden response.
     */
    protected function forbiddenResponse(string $message = 'Forbidden. You do not have permission to perform this action.'): JsonResponse
    {
        return $this->errorResponse($message, 403);
    }

    /**
     * Return a 404 not found response.
     */
    protected function notFoundResponse(string $message = 'Resource not found.'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }

    /**
     * Return a 422 validation error response.
     */
    protected function validationErrorResponse(mixed $errors, string $message = 'Validation failed.'): JsonResponse
    {
        return $this->errorResponse($message, 422, $errors);
    }
}
