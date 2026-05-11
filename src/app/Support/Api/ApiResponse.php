<?php

namespace App\Support\Api;

final class ApiResponse
{
    public static function successPayload(
        mixed $data = null,
        string $message = ApiError::SUCCESS_MESSAGE,
        int $status = 200,
        ?string $traceId = null,
        mixed $meta = null,
        ?string $userMessage = null
    ): array {
        return [
            'success' => true,
            'code' => $status,
            'message' => $message,
            'user_message' => $userMessage,
            'data' => $data,
            'meta' => $meta,
            'trace_id' => $traceId,
        ];
    }

    public static function errorPayload(
        int $status,
        string $error,
        ?string $message = null,
        mixed $errors = null,
        ?string $traceId = null,
        mixed $debug = null,
        ?string $userMessage = null
    ): array {
        return [
            'success' => false,
            'code' => $status,
            'error' => $error,
            'message' => $message ?? $error,
            'user_message' => $userMessage,
            'errors' => $errors,
            'trace_id' => $traceId,
            'debug' => $debug,
        ];
    }

    private function __construct()
    {
    }
}
