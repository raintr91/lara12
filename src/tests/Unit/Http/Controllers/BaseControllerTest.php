<?php

namespace Tests\Unit\Http\Controllers;

use Tests\TestCase;
use App\Http\Controllers\BaseController;

class BaseControllerTest extends TestCase
{
    protected BaseController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new BaseController();
    }

    /**
     * Test success response returns JSON with success true.
     */
    public function test_success_response_structure(): void
    {
        $response = $this->controller->success(
            ['id' => 1, 'name' => 'John'],
            'Resource created',
            201
        );

        $this->assertEquals(201, $response->status());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('Resource created', $data['message']);
        $this->assertIsArray($data['data']);
    }

    /**
     * Test success response with null data.
     */
    public function test_success_response_null_data(): void
    {
        $response = $this->controller->success();

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertNull($data['data']);
    }

    /**
     * Test error response returns JSON with success false.
     */
    public function test_error_response_structure(): void
    {
        $response = $this->controller->error('Resource not found', 404);

        $this->assertEquals(404, $response->status());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Resource not found', $data['message']);
    }

    /**
     * Test error response with errors array.
     */
    public function test_error_response_with_errors(): void
    {
        $errors = ['email' => ['Email already exists']];
        $response = $this->controller->error('Validation failed', 422, $errors);

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals($errors, $data['errors']);
    }

    /**
     * Test default error code is 400.
     */
    public function test_error_default_code(): void
    {
        $response = $this->controller->error('Bad request');

        $this->assertEquals(400, $response->status());
    }

    public function test_error_labels_for_specific_status_codes(): void
    {
        $cases = [401, 403, 500];

        foreach ($cases as $status) {
            $response = $this->controller->error('Err', $status);
            $data = json_decode($response->getContent(), true);

            $this->assertFalse($data['success']);
            $this->assertSame($status, $data['code']);
            $this->assertNotEmpty($data['error']);
        }
    }

    public function test_trace_id_is_included_when_bound(): void
    {
        app()->instance('request_id', 'rid-controller');

        $response = $this->controller->success(['ok' => true]);
        $data = json_decode($response->getContent(), true);

        $this->assertSame('rid-controller', $data['trace_id']);

        app()->forgetInstance('request_id');
    }
}
