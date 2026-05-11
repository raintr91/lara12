<?php

namespace App\Support\Api;

final class ApiError
{
    public const STATUS_BAD_REQUEST = 400;
    public const STATUS_UNAUTHORIZED = 401;
    public const STATUS_FORBIDDEN = 403;
    public const STATUS_NOT_FOUND = 404;
    public const STATUS_UNPROCESSABLE_ENTITY = 422;
    public const STATUS_INTERNAL_SERVER_ERROR = 500;

    public const SUCCESS_MESSAGE = 'Success';
    public const DEFAULT_ERROR = 'Error';

    public const VALIDATION_ERROR = 'Validation Error';
    public const VALIDATION_FAILED_MESSAGE = 'Invalid input data';

    public const UNAUTHENTICATED = 'Unauthenticated';
    public const FORBIDDEN = 'Forbidden';
    public const NOT_FOUND = 'Not Found';
    public const INTERNAL_SERVER_ERROR = 'Internal Server Error';

    private function __construct()
    {
    }
}
