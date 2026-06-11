<?php

namespace Tests\Unit\Support\Api;

use App\Support\Api\ApiError;
use Tests\Unit\UnitTestCase;

/**
 * ApiError — pure constants class, kiểm tra giá trị hằng không bị thay đổi.
 */
class ApiErrorTest extends UnitTestCase
{
    public function test_http_status_constants(): void
    {
        $this->assertSame(400, ApiError::STATUS_BAD_REQUEST);
        $this->assertSame(401, ApiError::STATUS_UNAUTHORIZED);
        $this->assertSame(403, ApiError::STATUS_FORBIDDEN);
        $this->assertSame(404, ApiError::STATUS_NOT_FOUND);
        $this->assertSame(422, ApiError::STATUS_UNPROCESSABLE_ENTITY);
        $this->assertSame(500, ApiError::STATUS_INTERNAL_SERVER_ERROR);
    }

    public function test_message_constants(): void
    {
        $this->assertSame('Success', ApiError::SUCCESS_MESSAGE);
        $this->assertSame('Error', ApiError::DEFAULT_ERROR);
        $this->assertSame('Validation Error', ApiError::VALIDATION_ERROR);
        $this->assertSame('Invalid input data', ApiError::VALIDATION_FAILED_MESSAGE);
        $this->assertSame('Unauthenticated', ApiError::UNAUTHENTICATED);
        $this->assertSame('Forbidden', ApiError::FORBIDDEN);
        $this->assertSame('Not Found', ApiError::NOT_FOUND);
        $this->assertSame('Internal Server Error', ApiError::INTERNAL_SERVER_ERROR);
    }

    public function test_private_constructor_can_only_be_invoked_via_reflection(): void
    {
        $reflection = new \ReflectionClass(ApiError::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);
        $this->assertTrue($constructor->isPrivate());

        $instance = $reflection->newInstanceWithoutConstructor();
        $constructor->setAccessible(true);
        $constructor->invoke($instance);

        $this->assertInstanceOf(ApiError::class, $instance);
    }
}
