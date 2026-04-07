<?php

namespace App\Http;

use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    /**
     * Standard success response.
     *
     * @param  mixed  $data
     * @param  array<string, mixed>  $meta
     */
    public static function success(mixed $data = [], array $meta = [], int $status = 200): JsonResponse
    {
        $body = ['data' => $data];

        if (!empty($meta)) {
            $body['meta'] = $meta;
        }

        return response()->json($body, $status);
    }

    /**
     * Standard created (201) response.
     *
     * @param  mixed  $data
     * @param  array<string, mixed>  $meta
     */
    public static function created(mixed $data = [], array $meta = []): JsonResponse
    {
        return self::success($data, $meta, 201);
    }

    /**
     * Standard error response.
     */
    public static function error(string $message, int $status = 400, string $errorCode = ''): JsonResponse
    {
        $body = [
            'error' => true,
            'message' => $message,
        ];

        if ($errorCode !== '') {
            $body['error_code'] = $errorCode;
        }

        return response()->json($body, $status);
    }

    /**
     * 422 validation error response with field-level errors.
     *
     * @param  array<string, string[]>  $errors
     */
    public static function validationError(array $errors, string $message = 'Validation failed.'): JsonResponse
    {
        return response()->json([
            'error' => true,
            'message' => $message,
            'errors' => $errors,
        ], 422);
    }

    /**
     * 204 No Content response.
     */
    public static function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
