<?php

namespace App\Http\Controllers;

use App\Support\Api\ApiError;
use App\Support\Api\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;

class BaseController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Success response.
     *
     * @param mixed $data
     * @param string $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function success($data = null, $message = ApiError::SUCCESS_MESSAGE, $code = 200, $meta = null)
    {
        return response()->json(
            ApiResponse::successPayload($data, $message, $code, $this->traceId(), $meta),
            $code
        );
    }

    /**
     * Error response.
     *
     * @param string $message
     * @param int $code
     * @param mixed $errors
     * @return \Illuminate\Http\JsonResponse
     */
    public function error($message = ApiError::DEFAULT_ERROR, $code = ApiError::STATUS_BAD_REQUEST, $errors = null, ?string $userMessage = null)
    {
        return response()->json(
            ApiResponse::errorPayload(
                $code,
                $this->errorLabelFromStatus($code),
                $message,
                $errors,
                $this->traceId(),
                null,
                $userMessage
            ),
            $code
        );
    }

    protected function traceId(): ?string
    {
        return app()->bound('request_id') ? (string) app('request_id') : null;
    }

    protected function errorLabelFromStatus(int $status): string
    {
        return match ($status) {
            ApiError::STATUS_UNAUTHORIZED => ApiError::UNAUTHENTICATED,
            ApiError::STATUS_FORBIDDEN => ApiError::FORBIDDEN,
            ApiError::STATUS_NOT_FOUND => ApiError::NOT_FOUND,
            ApiError::STATUS_UNPROCESSABLE_ENTITY => ApiError::VALIDATION_ERROR,
            ApiError::STATUS_INTERNAL_SERVER_ERROR => ApiError::INTERNAL_SERVER_ERROR,
            default => ApiError::DEFAULT_ERROR,
        };
    }

}
