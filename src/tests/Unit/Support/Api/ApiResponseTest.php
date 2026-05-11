<?php

namespace Tests\Unit\Support\Api;

use App\Support\Api\ApiResponse;
use App\Support\Api\ApiError;
use Tests\TestCase;

/**
 * ApiResponse — pure static helper, không cần DB / mock.
 */
class ApiResponseTest extends TestCase
{
    // ------------------------------------------------------------------
    // successPayload
    // ------------------------------------------------------------------

    public function test_success_payload_default_values(): void
    {
        $payload = ApiResponse::successPayload();

        $this->assertTrue($payload['success']);
        $this->assertSame(200, $payload['code']);
        $this->assertSame(ApiError::SUCCESS_MESSAGE, $payload['message']);
        $this->assertNull($payload['data']);
        $this->assertNull($payload['meta']);
        $this->assertNull($payload['trace_id']);
    }

    public function test_success_payload_with_data(): void
    {
        $data    = ['id' => 1, 'name' => 'Alice'];
        $payload = ApiResponse::successPayload($data, 'Created', 201);

        $this->assertTrue($payload['success']);
        $this->assertSame(201, $payload['code']);
        $this->assertSame('Created', $payload['message']);
        $this->assertSame($data, $payload['data']);
    }

    public function test_success_payload_with_trace_id(): void
    {
        $payload = ApiResponse::successPayload(null, 'OK', 200, 'trace-abc');

        $this->assertSame('trace-abc', $payload['trace_id']);
    }

    public function test_success_payload_with_meta(): void
    {
        $meta    = ['total' => 100, 'per_page' => 15, 'current_page' => 1];
        $payload = ApiResponse::successPayload(['items' => []], 'Listed', 200, null, $meta);

        $this->assertSame($meta, $payload['meta']);
    }

    public function test_success_payload_keys_present(): void
    {
        $payload = ApiResponse::successPayload();

        foreach (['success', 'code', 'message', 'data', 'meta', 'trace_id'] as $key) {
            $this->assertArrayHasKey($key, $payload);
        }
    }

    // ------------------------------------------------------------------
    // errorPayload
    // ------------------------------------------------------------------

    public function test_error_payload_default_values(): void
    {
        $payload = ApiResponse::errorPayload(500, 'Internal Server Error');

        $this->assertFalse($payload['success']);
        $this->assertSame(500, $payload['code']);
        $this->assertSame('Internal Server Error', $payload['error']);
        // message defaults to error when not set
        $this->assertSame('Internal Server Error', $payload['message']);
        $this->assertNull($payload['errors']);
        $this->assertNull($payload['trace_id']);
        $this->assertNull($payload['debug']);
    }

    public function test_error_payload_custom_message(): void
    {
        $payload = ApiResponse::errorPayload(422, 'Validation Error', 'Invalid input data');

        $this->assertSame('Validation Error', $payload['error']);
        $this->assertSame('Invalid input data', $payload['message']);
    }

    public function test_error_payload_with_errors_array(): void
    {
        $errors  = ['email' => ['Email is required']];
        $payload = ApiResponse::errorPayload(422, 'Validation Error', null, $errors);

        $this->assertSame($errors, $payload['errors']);
    }

    public function test_error_payload_with_trace_id(): void
    {
        $payload = ApiResponse::errorPayload(500, 'Error', null, null, 'req-123');

        $this->assertSame('req-123', $payload['trace_id']);
    }

    public function test_error_payload_with_debug(): void
    {
        $debug   = ['exception' => 'RuntimeException', 'file' => 'Handler.php', 'line' => 42];
        $payload = ApiResponse::errorPayload(500, 'Error', null, null, null, $debug);

        $this->assertSame($debug, $payload['debug']);
    }

    public function test_error_payload_keys_present(): void
    {
        $payload = ApiResponse::errorPayload(400, 'Bad Request');

        foreach (['success', 'code', 'error', 'message', 'errors', 'trace_id', 'debug'] as $key) {
            $this->assertArrayHasKey($key, $payload);
        }
    }

    // ------------------------------------------------------------------
    // Edge cases
    // ------------------------------------------------------------------

    public function test_success_payload_data_can_be_empty_array(): void
    {
        $payload = ApiResponse::successPayload([]);

        $this->assertSame([], $payload['data']);
    }

    public function test_success_payload_data_can_be_scalar(): void
    {
        $payload = ApiResponse::successPayload(42);

        $this->assertSame(42, $payload['data']);
    }

    public function test_error_payload_null_message_falls_back_to_error_string(): void
    {
        $payload = ApiResponse::errorPayload(404, 'Not Found', null);

        $this->assertSame('Not Found', $payload['message']);
    }

    public function test_private_constructor_is_not_publicly_accessible(): void
    {
        $ref = new \ReflectionClass(ApiResponse::class);
        $ctor = $ref->getConstructor();

        $this->assertNotNull($ctor);
        $this->assertTrue($ctor->isPrivate());

        $instance = $ref->newInstanceWithoutConstructor();
        $ctor->setAccessible(true);
        $ctor->invoke($instance);

        $this->assertInstanceOf(ApiResponse::class, $instance);
    }
}
