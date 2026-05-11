<?php

namespace Tests\Unit\Http\Controllers\Traits;

use App\Http\Actions\BaseAction;
use App\Http\Controllers\BaseController;
use App\Http\Controllers\Traits\EntryBulkDeleteTrait;
use App\Http\Controllers\Traits\EntryCreateTrait;
use App\Http\Controllers\Traits\EntryDeleteTrait;
use App\Http\Controllers\Traits\EntryUpdateTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class EntryCrudTraitsTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_create_success(): void
    {
        $controller = new class extends BaseController {
            use EntryCreateTrait;
        };

        $action = new class extends BaseAction {
            protected function run(...$args)
            {
                return ['payload' => $args[0] ?? []];
            }
        };

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validated')->once()->andReturn(['name' => 'robot']);

        $response = $controller->create($action, $request);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(201, $response->status());
        $this->assertTrue($data['success']);
        $this->assertSame('Created successfully', $data['message']);
        $this->assertSame('create', $data['data']['payload']['operation']);
    }

    public function test_create_error(): void
    {
        $controller = new class extends BaseController {
            use EntryCreateTrait;
        };

        $action = new class extends BaseAction {
            protected function run(...$args)
            {
                throw new \RuntimeException('create failed');
            }
        };

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validated')->once()->andReturn(['name' => 'robot']);

        $response = $controller->create($action, $request);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $response->status());
        $this->assertFalse($data['success']);
        $this->assertSame('create failed', $data['message']);
    }

    public function test_update_success(): void
    {
        $controller = new class extends BaseController {
            use EntryUpdateTrait;
        };

        $action = new class extends BaseAction {
            protected function run(...$args)
            {
                return ['payload' => $args[0] ?? []];
            }
        };

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validated')->once()->andReturn(['name' => 'robot']);

        $response = $controller->update($action, $request, 55);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->status());
        $this->assertTrue($data['success']);
        $this->assertSame('Updated successfully', $data['message']);
        $this->assertSame(55, $data['data']['payload']['id']);
        $this->assertSame('update', $data['data']['payload']['operation']);
    }

    public function test_update_error(): void
    {
        $controller = new class extends BaseController {
            use EntryUpdateTrait;
        };

        $action = new class extends BaseAction {
            protected function run(...$args)
            {
                throw new \RuntimeException('update failed');
            }
        };

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validated')->once()->andReturn(['name' => 'robot']);

        $response = $controller->update($action, $request, 55);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $response->status());
        $this->assertFalse($data['success']);
        $this->assertSame('update failed', $data['message']);
    }

    public function test_delete_success(): void
    {
        $controller = new class extends BaseController {
            use EntryDeleteTrait;
        };

        $action = new class extends BaseAction {
            protected function run(...$args)
            {
                return null;
            }
        };

        $response = $controller->delete($action, 7);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->status());
        $this->assertTrue($data['success']);
        $this->assertSame('Deleted successfully', $data['message']);
    }

    public function test_delete_error(): void
    {
        $controller = new class extends BaseController {
            use EntryDeleteTrait;
        };

        $action = new class extends BaseAction {
            protected function run(...$args)
            {
                throw new \RuntimeException('delete failed');
            }
        };

        $response = $controller->delete($action, 7);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $response->status());
        $this->assertFalse($data['success']);
        $this->assertSame('delete failed', $data['message']);
    }

    public function test_bulk_delete_success_with_form_request_payload(): void
    {
        $controller = new class extends BaseController {
            use EntryBulkDeleteTrait;
        };

        $action = new class extends BaseAction {
            protected function run(...$args)
            {
                return ['payload' => $args[0] ?? []];
            }
        };

        $request = Mockery::mock(FormRequest::class);
        $request->shouldReceive('validated')->once()->andReturn(['ids' => [1, 2, 3]]);

        $response = $controller->bulkDelete($action, $request);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->status());
        $this->assertTrue($data['success']);
        $this->assertSame('Deleted successfully', $data['message']);
        $this->assertSame('bulk_delete', $data['data']['payload']['operation']);
        $this->assertSame([1, 2, 3], $data['data']['payload']['ids']);
    }

    public function test_bulk_delete_success_with_plain_request_payload(): void
    {
        $controller = new class extends BaseController {
            use EntryBulkDeleteTrait;
        };

        $action = new class extends BaseAction {
            protected function run(...$args)
            {
                return ['payload' => $args[0] ?? []];
            }
        };

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('all')->once()->andReturn(['ids' => [9]]);

        $response = $controller->bulkDelete($action, $request, 'delete_many');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->status());
        $this->assertTrue($data['success']);
        $this->assertSame('delete_many', $data['data']['payload']['operation']);
        $this->assertSame([9], $data['data']['payload']['ids']);
    }

    public function test_bulk_delete_error(): void
    {
        $controller = new class extends BaseController {
            use EntryBulkDeleteTrait;
        };

        $action = new class extends BaseAction {
            protected function run(...$args)
            {
                throw new \RuntimeException('bulk delete failed');
            }
        };

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('all')->once()->andReturn(['ids' => [1]]);

        $response = $controller->bulkDelete($action, $request);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $response->status());
        $this->assertFalse($data['success']);
        $this->assertSame('bulk delete failed', $data['message']);
    }
}
