<?php

namespace App\Exceptions;

use App\Support\Api\ApiError;
use App\Support\Api\ApiResponse;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->renderable(function (Throwable $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return $this->handleApiException($e);
        });
    }

    protected function handleApiException(Throwable $e)
    {
        return match (true) {
            $e instanceof ValidationException =>
                $this->validationError($e),

            $e instanceof AuthenticationException =>
                $this->errorResponse(ApiError::STATUS_UNAUTHORIZED, ApiError::UNAUTHENTICATED, $e->getMessage()),

            $e instanceof AuthorizationException =>
                $this->errorResponse(ApiError::STATUS_FORBIDDEN, ApiError::FORBIDDEN, $e->getMessage()),

            $e instanceof ModelNotFoundException =>
                $this->errorResponse(
                    ApiError::STATUS_NOT_FOUND,
                    ApiError::NOT_FOUND,
                    class_basename($e->getModel()) . ' not found'
                ),

            $e instanceof HttpExceptionInterface =>
                $this->errorResponse(
                    $e->getStatusCode(),
                    Response::$statusTexts[$e->getStatusCode()] ?? 'Error',
                    $e->getMessage()
                ),

            default =>
                $this->serverError($e),
        };
    }

    protected function validationError(ValidationException $e)
    {
        return response()->json(
            ApiResponse::errorPayload(
                ApiError::STATUS_UNPROCESSABLE_ENTITY,
                ApiError::VALIDATION_ERROR,
                ApiError::VALIDATION_FAILED_MESSAGE,
                $e->errors(),
                $this->traceId()
            ),
            ApiError::STATUS_UNPROCESSABLE_ENTITY
        );
    }

    protected function errorResponse(
        int $status,
        string $error,
        ?string $message
    ) {
        return response()->json(
            ApiResponse::errorPayload(
                $status,
                $error,
                $message,
                null,
                $this->traceId(),
                null,
                $this->userMessageFromStatus($status)
            ),
            $status
        );
    }

    protected function serverError(Throwable $e)
    {
        report($e);

        $debug = config('app.debug')
            ? Arr::only([
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], ['exception', 'file', 'line'])
            : null;

        return response()->json(
            ApiResponse::errorPayload(
                ApiError::STATUS_INTERNAL_SERVER_ERROR,
                ApiError::INTERNAL_SERVER_ERROR,
                config('app.debug') ? $e->getMessage() : 'Something went wrong',
                null,
                $this->traceId(),
                $debug,
                $this->userMessageFromStatus(ApiError::STATUS_INTERNAL_SERVER_ERROR)
            ),
            ApiError::STATUS_INTERNAL_SERVER_ERROR
        );
    }

    protected function userMessageFromStatus(int $status): string
    {
        return match ($status) {
            ApiError::STATUS_BAD_REQUEST => 'リクエスト内容が不正です。入力内容をご確認ください。',
            ApiError::STATUS_UNAUTHORIZED => 'セッションの有効期限が切れました。再度ログインしてください。',
            ApiError::STATUS_FORBIDDEN => 'この操作を実行する権限がありません。',
            ApiError::STATUS_NOT_FOUND => 'データが見つかりません。',
            ApiError::STATUS_UNPROCESSABLE_ENTITY => '入力内容に不備があります。必須項目をご確認ください。',
            default => $status >= 500
                ? sprintf('サーバー処理中にエラーが発生しました。（%d）', $status)
                : 'エラーが発生しました。しばらくしてから再度お試しください。',
        };
    }

    protected function traceId(): ?string
    {
        return app()->bound('request_id')
            ? app('request_id')
            : null;
    }
}
