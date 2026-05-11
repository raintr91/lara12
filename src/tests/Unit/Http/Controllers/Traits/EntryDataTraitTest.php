<?php

namespace Tests\Unit\Http\Controllers\Traits;

use Tests\TestCase;
use App\Http\Controllers\Traits\EntryCreateTrait;
use App\Http\Controllers\BaseController;
use App\Http\Actions\BaseAction;
use Illuminate\Http\Request;
use Mockery;

class EntryDataTraitTest extends TestCase
{
    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = new class extends BaseController {
            use EntryCreateTrait;
        };
    }

    public function test_create_returns_success_response(): void
    {
        $action = new class extends BaseAction {
            protected function run(...$args)
            {
                return ['ok' => true, 'payload' => $args[0] ?? null];
            }
        };

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validated')->andReturn(['name' => 'John']);

        $response = $this->controller->create($action, $request);

        $this->assertSame(201, $response->status());

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertSame('Created successfully', $data['message']);
        $this->assertSame(true, $data['data']['ok']);
        $this->assertSame('create', $data['data']['payload']['operation']);
        $this->assertSame('John', $data['data']['payload']['name']);
    }
}
