<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\MessageBag;
use Mockery;
use Tests\TestCase;

class BaseRequestValidationTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_authorize_returns_true(): void
    {
        $request = new BaseRequest();

        $this->assertTrue($request->authorize());
    }

    public function test_failed_validation_returns_standard_json_shape(): void
    {
        $request = new class extends BaseRequest {
            public function triggerFailedValidation(Validator $validator): void
            {
                $this->failedValidation($validator);
            }
        };

        $validator = Mockery::mock(Validator::class);
        $validator
            ->shouldReceive('errors')
            ->once()
            ->andReturn(new MessageBag(['name' => ['The name field is required.']]));

        try {
            $request->triggerFailedValidation($validator);
            $this->fail('Expected HttpResponseException was not thrown.');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
            $data = json_decode($response->getContent(), true);

            $this->assertSame(422, $response->status());
            $this->assertFalse($data['success']);
            $this->assertSame('Invalid input data', $data['message']);
            $this->assertArrayHasKey('name', $data['errors']);
        }
    }
}
