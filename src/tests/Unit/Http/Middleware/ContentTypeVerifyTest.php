<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\ContentTypeVerify;
use Illuminate\Http\Request;
use Tests\Unit\UnitTestCase;

class ContentTypeVerifyTest extends UnitTestCase
{
    private ContentTypeVerify $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new ContentTypeVerify();
    }

    public function test_handle_allows_json_request(): void
    {
        $request = Request::create('/', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json']);
        $request->setJson(json_decode('{}', false));
        
        $next = fn($req) => response()->json(['status' => 'ok']);

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(200, $response->status());
        $this->assertEquals(['status' => 'ok'], json_decode($response->getContent(), true));
    }

    public function test_handle_allows_request_with_json_content_type(): void
    {
        $request = Request::create('/', 'POST', [], [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ]);
        
        $next = fn($req) => response()->json(['status' => 'ok']);

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(200, $response->status());
    }

    public function test_handle_returns_406_when_content_type_is_form(): void
    {
        $request = Request::create('/', 'POST', ['key' => 'value']);
        $next = fn($req) => response()->json(['status' => 'ok']);

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(406, $response->status());
        $this->assertEquals(['message' => 'The Content-type value is invalid.'], json_decode($response->getContent(), true));
    }

    public function test_handle_returns_406_when_content_type_is_xml(): void
    {
        $request = Request::create('/', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/xml',
        ]);
        
        $next = fn($req) => response()->json(['status' => 'ok']);

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(406, $response->status());
        $this->assertEquals(['message' => 'The Content-type value is invalid.'], json_decode($response->getContent(), true));
    }

    public function test_handle_returns_406_for_html_content_type(): void
    {
        $request = Request::create('/', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'text/html',
        ]);
        
        $next = fn($req) => response()->json(['status' => 'ok']);

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(406, $response->status());
    }

    public function test_handle_returns_406_for_text_plain(): void
    {
        $request = Request::create('/', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'text/plain',
        ]);

        $next = fn($req) => response()->json(['status' => 'ok']);

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(406, $response->status());
    }
}
