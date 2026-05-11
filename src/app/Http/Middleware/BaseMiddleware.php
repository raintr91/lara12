<?php

namespace App\Http\Middleware;

use App\Support\Api\ApiError;
use App\Support\Api\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $this->before($request);

            $response = $next($request);

            return $this->after($request, $response);
        } catch (\Throwable $e) {
            return $this->handleException($e, $request);
        }
    }

    protected function before(Request $request): void {}

    protected function after(Request $request, Response $response): Response
    {
        return $response;
    }

    protected function handleException(\Throwable $e, Request $request): Response
    {
        if ($request->expectsJson()) {
            report($e);

            return response()->json(
                ApiResponse::errorPayload(
                    ApiError::STATUS_INTERNAL_SERVER_ERROR,
                    ApiError::INTERNAL_SERVER_ERROR,
                    config('app.debug') ? $e->getMessage() : 'Something went wrong',
                    null,
                    $this->traceId(),
                    config('app.debug')
                        ? [
                            'exception' => get_class($e),
                        ]
                        : null
                ),
                ApiError::STATUS_INTERNAL_SERVER_ERROR
            );
        }

        throw $e;
    }

    protected function successResponse(
        mixed $data = null,
        string $message = ApiError::SUCCESS_MESSAGE,
        int $status = 200
    ): Response {
        return response()->json(
            ApiResponse::successPayload($data, $message, $status, $this->traceId()),
            $status
        );
    }

    protected function errorResponse(
        int $status,
        string $error,
        ?string $message = null,
        mixed $errors = null
    ): Response {
        return response()->json(
            ApiResponse::errorPayload(
                $status,
                $error,
                $message,
                $errors,
                $this->traceId()
            ),
            $status
        );
    }

    protected function traceId(): ?string
    {
        return app()->bound('request_id') ? (string) app('request_id') : null;
    }
}
