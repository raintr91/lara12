<?php

namespace App\Http\Requests;

use App\Support\Api\ApiError;
use App\Support\Api\ApiResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class BaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (! $this->requiresAuthorization()) {
            return true;
        }

        return $this->authorizeRequest();
    }

    /**
     * Opt-in auth strategy for request classes.
     */
    protected function requiresAuthorization(): bool
    {
        return false;
    }

    /**
     * Override this in child requests when requiresAuthorization() = true.
     */
    protected function authorizeRequest(): bool
    {
        return $this->user() !== null;
    }

    protected function authenticatedUser()
    {
        return $this->user();
    }

    protected function hasAuthenticatedUser(): bool
    {
        return $this->authenticatedUser() !== null;
    }

    protected function traceId(): ?string
    {
        return app()->bound('request_id') ? (string) app('request_id') : null;
    }

    protected function validationMessage(): string
    {
        return ApiError::VALIDATION_FAILED_MESSAGE;
    }

    protected function validationErrorLabel(): string
    {
        return ApiError::VALIDATION_ERROR;
    }

    protected function validationStatus(): int
    {
        return ApiError::STATUS_UNPROCESSABLE_ENTITY;
    }

    /**
     * Hook to normalize/clean input before validation.
     * Override `normalizeInput(array $data): array` in child requests to customize.
     */
    protected function prepareForValidation(): void
    {
        $data = $this->all();
        $normalized = $this->normalizeInput($data);
        $this->replace($normalized);
    }

    /**
     * Default normalizer: trim strings and convert empty strings to null recursively.
     *
     * @param array $data
     * @return array
     */
    protected function normalizeInput(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $v = trim($value);
                $result[$key] = ($v === '') ? null : $v;
            } elseif (is_array($value)) {
                $result[$key] = $this->normalizeInput($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Customize the failed validation response: JSON with errors and message.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors()->toArray();

        $status = $this->validationStatus();

        $response = response()->json(
            ApiResponse::errorPayload(
                $status,
                $this->validationErrorLabel(),
                $this->validationMessage(),
                $errors,
                $this->traceId()
            ),
            $status
        );

        throw new HttpResponseException($response);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'min.string' => __('validation.min.string'),
            'max.string' => __('validation.max.string'),
        ]);
    }
}
