<?php

namespace Tests\Unit\Http\Controllers\Traits;

use App\Http\Actions\BaseAction;
use App\Http\Controllers\BaseController;
use App\Http\Controllers\Traits\EntryBulkDeleteTrait;
use App\Http\Requests\BulkDeleteRequest;
use App\Http\Controllers\Traits\EntryCreateTrait;
use App\Http\Controllers\Traits\EntryDeleteTrait;
use App\Http\Controllers\Traits\EntryUpdateTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Mockery;
use Tests\Unit\UnitTestCase;

class EntryCrudTraitsTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_create_success(): void
    {
        $controller = $this->controllerWithAction();
        $request = Mockery::mock(FormRequest::class);
        $request->shouldReceive('validated')->once()->andReturn(['name' => 'robot']);

        $controller->action->shouldReceive('execute')->once()->with(['name' => 'robot'])->andReturn(['id' => 1]);

        $response = $controller->create($request);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertSame(['id' => 1], $data['data']);
    }

    public function test_create_error(): void
    {
        $controller = $this->controllerWithAction();
        $request = Mockery::mock(FormRequest::class);
        $request->shouldReceive('validated')->once()->andReturn(['name' => 'robot']);
        $controller->action->shouldReceive('execute')->once()->andThrow(new \RuntimeException('create failed'));

        $response = $controller->create($request);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertSame('create failed', $data['message']);
    }

    public function test_update_success(): void
    {
        $controller = $this->controllerWithAction();
        $request = Mockery::mock(FormRequest::class);
        $request->shouldReceive('validated')->once()->andReturn(['name' => 'robot']);
        $controller->action->shouldReceive('update')->once()->with(55, ['name' => 'robot'])->andReturn(['id' => 55]);

        $response = $controller->update($request, 55);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertSame(['id' => 55], $data['data']);
    }

    public function test_delete_success(): void
    {
        $controller = $this->controllerWithAction();
        $controller->action->shouldReceive('delete')->once()->with(7)->andReturnNull();

        $response = $controller->delete(7);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertNull($data['data']);
    }

    public function test_bulk_delete_success(): void
    {
        $controller = $this->controllerWithAction();
        $controller->action->shouldReceive('bulkDelete')->once()->with([1, 2, 3])->andReturn(true);

        $response = $controller->bulkDelete($this->bulkDeleteRequest([1, 2, 3]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertTrue($data['data']);
    }

    public function test_create_uses_request_all_when_not_form_request(): void
    {
        $controller = $this->controllerWithAction();
        $request = Request::create('/', 'POST', ['name' => 'plain']);
        $controller->action->shouldReceive('execute')->once()->with(['name' => 'plain'])->andReturn(['id' => 2]);

        $response = $controller->create($request);
        $this->assertSame(201, $response->getStatusCode());
    }

    public function test_update_uses_request_all_when_not_form_request(): void
    {
        $controller = $this->controllerWithAction();
        $request = Request::create('/', 'PUT', ['name' => 'plain']);
        $controller->action->shouldReceive('update')->once()->with(4, ['name' => 'plain'])->andReturn(['id' => 4]);

        $response = $controller->update($request, 4);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_update_error(): void
    {
        $controller = $this->controllerWithAction();
        $request = Mockery::mock(FormRequest::class);
        $request->shouldReceive('validated')->once()->andReturn(['name' => 'x']);
        $controller->action->shouldReceive('update')->once()->andThrow(new \RuntimeException('update failed'));

        $response = $controller->update($request, 1);
        $this->assertSame(500, $response->getStatusCode());
    }

    public function test_delete_error(): void
    {
        $controller = $this->controllerWithAction();
        $controller->action->shouldReceive('delete')->once()->andThrow(new \RuntimeException('delete failed'));

        $response = $controller->delete(1);
        $this->assertSame(500, $response->getStatusCode());
    }

    public function test_bulk_delete_error(): void
    {
        $controller = $this->controllerWithAction();
        $controller->action->shouldReceive('bulkDelete')->once()->andThrow(new \RuntimeException('bulk failed'));

        $response = $controller->bulkDelete($this->bulkDeleteRequest([1]));
        $this->assertSame(500, $response->getStatusCode());
    }

    private function bulkDeleteRequest(array $ids): BulkDeleteRequest
    {
        $request = new class extends BulkDeleteRequest {
        };
        $request->merge(['ids' => $ids]);
        $request->setContainer(app())->setRedirector(app('redirect'));
        $request->validateResolved();

        return $request;
    }

    private function controllerWithAction()
    {
        $controller = new class extends BaseController {
            use EntryCreateTrait;
            use EntryUpdateTrait;
            use EntryDeleteTrait;
            use EntryBulkDeleteTrait;

            public BaseAction $action;
        };

        $controller->action = Mockery::mock(BaseAction::class);

        return $controller;
    }
}
