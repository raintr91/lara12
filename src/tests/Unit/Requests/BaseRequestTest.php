<?php

namespace Tests\Unit\Requests;

use Tests\Unit\UnitTestCase;
use App\Http\Requests\BaseRequest;
use App\Support\Api\ApiError;

class BaseRequestTest extends UnitTestCase
{
    private function makeRequest(): BaseRequest
    {
        return new class extends BaseRequest {
            public function publicNormalizeInput(array $data): array
            {
                return $this->normalizeInput($data);
            }
        };
    }

    /**
     * Test that BaseRequest normalizes input by trimming strings.
     */
    public function test_normalize_input_trims_strings(): void
    {
        $request = $this->makeRequest();
        $data = [
            'name' => '  John Doe  ',
            'email' => '  test@example.com  ',
            'age' => 25,
        ];

        $normalized = $request->publicNormalizeInput($data);

        $this->assertEquals('John Doe', $normalized['name']);
        $this->assertEquals('test@example.com', $normalized['email']);
        $this->assertEquals(25, $normalized['age']);
    }

    /**
     * Test that BaseRequest converts empty strings to null.
     */
    public function test_normalize_input_converts_empty_strings_to_null(): void
    {
        $request = $this->makeRequest();
        $data = [
            'name' => '',
            'email' => '  ',
            'description' => null,
        ];

        $normalized = $request->publicNormalizeInput($data);

        $this->assertNull($normalized['name']);
        $this->assertNull($normalized['email']);
        $this->assertNull($normalized['description']);
    }

    /**
     * Test that BaseRequest handles nested arrays.
     */
    public function test_normalize_input_handles_nested_arrays(): void
    {
        $request = $this->makeRequest();
        $data = [
            'user' => [
                'name' => '  John  ',
                'email' => '',
            ],
        ];

        $normalized = $request->publicNormalizeInput($data);

        $this->assertEquals('John', $normalized['user']['name']);
        $this->assertNull($normalized['user']['email']);
    }

    /**
     * Test default validation messages use i18n keys for string length rules.
     */
    public function test_default_validation_messages(): void
    {
        $request = new BaseRequest();
        $messages = $request->messages();

        $this->assertArrayHasKey('min.string', $messages);
        $this->assertArrayHasKey('max.string', $messages);
        $this->assertSame(__('validation.min.string'), $messages['min.string']);
        $this->assertSame(__('validation.max.string'), $messages['max.string']);
    }

    public function test_authorize_returns_true_when_no_authorization_required(): void
    {
        $request = new class extends BaseRequest {
            public function publicAuthorize(): bool
            {
                return $this->authorize();
            }
        };

        $this->assertTrue($request->publicAuthorize());
    }

    public function test_authorize_uses_authorize_request_when_required(): void
    {
        $request = new class extends BaseRequest {
            protected function requiresAuthorization(): bool
            {
                return true;
            }

            public function publicAuthorize(): bool
            {
                return $this->authorize();
            }
        };

        $request->setUserResolver(fn () => (object) ['id' => 1]);
        $this->assertTrue($request->publicAuthorize());

        $request->setUserResolver(fn () => null);
        $this->assertFalse($request->publicAuthorize());
    }

    public function test_auth_helper_methods_and_trace_id(): void
    {
        $request = new class extends BaseRequest {
            public function publicAuthenticatedUser()
            {
                return $this->authenticatedUser();
            }

            public function publicHasAuthenticatedUser(): bool
            {
                return $this->hasAuthenticatedUser();
            }

            public function publicTraceId(): ?string
            {
                return $this->traceId();
            }

            public function publicValidationMeta(): array
            {
                return [
                    $this->validationMessage(),
                    $this->validationErrorLabel(),
                    $this->validationStatus(),
                    $this->rules(),
                ];
            }
        };

        $request->setUserResolver(fn () => (object) ['id' => 9]);
        $this->assertNotNull($request->publicAuthenticatedUser());
        $this->assertTrue($request->publicHasAuthenticatedUser());

        app()->instance('request_id', 'rid-req');
        $this->assertSame('rid-req', $request->publicTraceId());

        app()->forgetInstance('request_id');
        $this->assertNull($request->publicTraceId());

        [$message, $label, $status, $rules] = $request->publicValidationMeta();
        $this->assertSame(ApiError::VALIDATION_FAILED_MESSAGE, $message);
        $this->assertSame(ApiError::VALIDATION_ERROR, $label);
        $this->assertSame(ApiError::STATUS_UNPROCESSABLE_ENTITY, $status);
        $this->assertSame([], $rules);
    }

    public function test_prepare_for_validation_replaces_input_with_normalized_values(): void
    {
        $request = new class extends BaseRequest {
            public function runPrepare(): void
            {
                $this->prepareForValidation();
            }
        };

        $request->replace([
            'name' => '  Alice  ',
            'empty' => '   ',
            'nested' => ['title' => '  Dev  '],
        ]);

        $request->runPrepare();

        $this->assertSame('Alice', $request->input('name'));
        $this->assertNull($request->input('empty'));
        $this->assertSame('Dev', data_get($request->all(), 'nested.title'));
    }
}
