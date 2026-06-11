<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\Handler;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use ReflectionMethod;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Unit\UnitTestCase;

class HandlerTest extends UnitTestCase
{
    protected Handler $handler;
    protected Request $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new Handler(app());

        // Ensure expectsJson() returns true.
        $this->request = Request::create('/', 'GET', server: ['HTTP_ACCEPT' => 'application/json']);

        // Register renderable callback.
        $this->handler->register();
    }

    protected function tearDown(): void
    {
        if (app()->bound('request_id')) {
            app()->offsetUnset('request_id');
        }

        config(['app.debug' => false]);

        parent::tearDown();
    }

    /** Log có thể in ERROR do {@see Handler::serverError} → {@see report()}; response vẫn dùng message generic khi debug tắt. */
    public function test_render_returns_json(): void
    {
        config(['app.debug' => false]);

        $exception = new \RuntimeException('HandlerTest::test_render_returns_json (expected)');
        $response = $this->handler->render($this->request, $exception);

        $this->assertEquals(500, $response->status());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertSame(500, $data['code']);
        $this->assertSame('Internal Server Error', $data['error']);
        $this->assertSame('Something went wrong', $data['message']);
        $this->assertNull($data['trace_id']);
        $this->assertNull($data['debug']);
    }

    /**
     * Test validation exception returns 422.
     */
    public function test_validation_exception_returns_422(): void
    {
        $errors = ['email' => ['Email is required']];
        $exception = ValidationException::withMessages($errors);

        $response = $this->invokeHandleApiException($exception);

        $this->assertEquals(422, $response->status());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertSame(422, $data['code']);
        $this->assertSame('Validation Error', $data['error']);
        $this->assertSame('Invalid input data', $data['message']);
        $this->assertSame($errors, $data['errors']);
        $this->assertNull($data['trace_id']);
    }

    /**
     * Test authentication exception returns 401.
     */
    public function test_authentication_exception_returns_401(): void
    {
        $exception = new AuthenticationException('Unauthenticated');
        $response = $this->invokeHandleApiException($exception);

        $this->assertEquals(401, $response->status());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertSame(401, $data['code']);
        $this->assertSame('Unauthenticated', $data['error']);
        $this->assertSame('Unauthenticated', $data['message']);
    }

    /**
     * Test authorization exception returns 403.
     */
    public function test_authorization_exception_returns_403(): void
    {
        $exception = new AuthorizationException('Forbidden');
        $response = $this->invokeHandleApiException($exception);

        $this->assertEquals(403, $response->status());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertSame(403, $data['code']);
        $this->assertSame('Forbidden', $data['error']);
        $this->assertSame('Forbidden', $data['message']);
    }

    /**
     * Test model not found exception returns 404.
     */
    public function test_model_not_found_returns_404(): void
    {
        $exception = new ModelNotFoundException();
        $exception->setModel(User::class, [1]);

        $response = $this->invokeHandleApiException($exception);

        $this->assertEquals(404, $response->status());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertSame(404, $data['code']);
        $this->assertSame('Not Found', $data['error']);
        $this->assertSame('User not found', $data['message']);
    }

    public function test_http_exception_returns_status_and_message(): void
    {
        $exception = new HttpException(429, 'Too many attempts');

        $response = $this->invokeHandleApiException($exception);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(429, $response->status());
        $this->assertFalse($data['success']);
        $this->assertSame(429, $data['code']);
        $this->assertSame('Too Many Requests', $data['error']);
        $this->assertSame('Too many attempts', $data['message']);
    }

    /** Terminal có thể in ERROR (do {@see report()}); message khớp exception bên dưới. */
    public function test_server_error_in_debug_mode_contains_debug_payload(): void
    {
        config(['app.debug' => true]);
        app()->instance('request_id', 'req-debug-001');

        $msg = 'HandlerTest::test_server_error_in_debug_mode_contains_debug_payload (expected)';
        $exception = new \RuntimeException($msg);
        $response = $this->invokeHandleApiException($exception);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $response->status());
        $this->assertSame($msg, $data['message']);
        $this->assertSame('req-debug-001', $data['trace_id']);
        $this->assertIsArray($data['debug']);
        $this->assertSame(\RuntimeException::class, $data['debug']['exception']);
        $this->assertArrayHasKey('file', $data['debug']);
        $this->assertArrayHasKey('line', $data['debug']);
    }

    public function test_non_json_request_falls_back_to_parent_renderer(): void
    {
        $request = Request::create('/', 'GET');
        $exception = new \RuntimeException('HandlerTest::test_non_json_request_falls_back_to_parent_renderer (expected)');

        $response = $this->handler->render($request, $exception);

        $this->assertNotSame('application/json', $response->headers->get('content-type'));
    }

    /** Cố ý ném lỗi chưa match để vào {@see Handler::serverError} (có {@see report()} → log ERROR); message rõ ràng để khỏi nhầm với lỗi runtime. */
    public function test_response_structure_with_trace_id_bound(): void
    {
        app()->instance('request_id', 'trace-123');

        $exception = new \RuntimeException('HandlerTest::test_response_structure_with_trace_id_bound (expected)');
        $response = $this->invokeHandleApiException($exception);

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('code', $data);
        $this->assertArrayHasKey('error', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('trace_id', $data);
        $this->assertSame('trace-123', $data['trace_id']);
    }

    private function invokeHandleApiException(\Throwable $exception)
    {
        $method = new ReflectionMethod(Handler::class, 'handleApiException');
        $method->setAccessible(true);

        return $method->invoke($this->handler, $exception);
    }
}
