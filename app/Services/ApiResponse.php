<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;

/**
 * The one place JSON responses are shaped. When BISU publishes its standard JSON shape, change it here.
 */
class ApiResponse
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public static function ok(mixed $data = null, array $meta = [], int $status = 200): JsonResponse
    {
        return new JsonResponse(['data' => $data, 'meta' => (object) $meta], $status);
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    public static function error(string $message, array $errors = [], int $status = 422): JsonResponse
    {
        return new JsonResponse(['message' => $message, 'errors' => (object) $errors], $status);
    }
}
