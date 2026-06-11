<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\BaseMiddleware;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Mockery;
use Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class TestableMiddleware extends BaseMiddleware
{
    public bool $throwBefore = false;

    protected function before(Request $request): void
    {
        if ($this->throwBefore) {
            throw new \RuntimeException('middleware boom');
        }
    }

    protected function after(Request $request, SymfonyResponse $response): SymfonyResponse
    {
        $response->headers->set('X-Middleware', 'after');
        return $response;
    }

    public function publicSuccess(mixed $data = null): SymfonyResponse
    {
        return $this->successResponse($data);
    }

    public function publicError(int $status, string $error): SymfonyResponse
    {
        return $this->errorResponse($status, $error);
    }

    public function publicTraceId(): ?string
    {
        return $this->traceId();
    }
}

class BaseMiddlewareTest extends UnitTestCase
{
    protected TestableMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        // Avoid noisy testing logs produced by report($e) inside middleware exception handling.
        $exceptionHandler = Mockery::mock(ExceptionHandler::class)->shouldIgnoreMissing();
        $exceptionHandler->shouldReceive('report')->andReturnNull();
        app()->instance(ExceptionHandler::class, $exceptionHandler);

        $this->middleware = new TestableMiddleware();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test middleware passes request to next.
     */
    public function test_middleware_passes_request_to_next(): void
    {
        $request = new Request();
        $next = function ($request) {
            return new Response('Next middleware', 200);
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('Next middleware', $response->getContent());
        $this->assertSame('after', $response->headers->get('X-Middleware'));
    }

    /**
     * Test middleware preserves request data.
     */
    public function test_middleware_preserves_request_data(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['test' => 'data']));
        $request->headers->set('Content-Type', 'application/json');

        $next = function ($request) {
            return new Response($request->getContent(), 200);
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertStringContainsString('test', $response->getContent());
    }

    public function test_json_exception_returns_standard_error_payload(): void
    {
        $request = Request::create('/api', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $request->headers->set('Accept', 'application/json');

        $this->middleware->throwBefore = true;

        $response = $this->middleware->handle($request, fn () => new Response('OK', 200));
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertFalse($payload['success']);
        $this->assertSame(500, $payload['code']);
    }

    public function test_json_exception_in_debug_mode_includes_debug_exception_class(): void
    {
        config(['app.debug' => true]);

        $request = Request::create('/api', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $request->headers->set('Accept', 'application/json');

        $this->middleware->throwBefore = true;

        $response = $this->middleware->handle($request, fn () => new Response('OK', 200));
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('middleware boom', $payload['message']);
        $this->assertIsArray($payload['debug']);
        $this->assertSame(\RuntimeException::class, $payload['debug']['exception']);

        config(['app.debug' => false]);
    }

    public function test_non_json_exception_is_rethrown(): void
    {
        $this->expectException(\RuntimeException::class);

        $request = Request::create('/web', 'GET');
        $this->middleware->throwBefore = true;

        $this->middleware->handle($request, fn () => new Response('OK', 200));
    }

    public function test_success_and_error_helper_responses(): void
    {
        app()->instance('request_id', 'rid-123');

        $okPayload = json_decode($this->middleware->publicSuccess(['a' => 1])->getContent(), true);
        $errPayload = json_decode($this->middleware->publicError(400, 'Bad Request')->getContent(), true);

        $this->assertTrue($okPayload['success']);
        $this->assertSame('rid-123', $okPayload['trace_id']);
        $this->assertFalse($errPayload['success']);
        $this->assertSame(400, $errPayload['code']);
    }

    public function test_trace_id_returns_null_when_not_bound(): void
    {
        app()->forgetInstance('request_id');
        $this->assertNull($this->middleware->publicTraceId());
    }

    public function test_base_middleware_default_before_after_path(): void
    {
        $base = new BaseMiddleware();
        $request = Request::create('/base', 'GET');

        $response = $base->handle($request, fn () => new Response('base', 200));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('base', $response->getContent());
    }
}
