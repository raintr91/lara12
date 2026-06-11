<?php

namespace Tests\Unit\Http\Controllers\Traits;

use App\Http\Controllers\BaseController;
use App\Http\Controllers\Traits\SelectItemControllerTrait;
use App\Http\Queries\BaseQuery;
use App\Http\Requests\SelectItemRequest;
use App\Http\Resources\SelectItemResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Mockery;
use Tests\Unit\UnitTestCase;

class SelectItemControllerTraitTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_list_select_returns_success_payload(): void
    {
        $controller = new class extends BaseController {
            use SelectItemControllerTrait;

            public BaseQuery $query;

            protected function selectItemResourceClass(): string
            {
                return SelectItemControllerResource::class;
            }
        };

        $request = Mockery::mock(SelectItemRequest::class);
        $items = collect([
            ['value' => 1, 'label' => 'first'],
            ['value' => 2, 'label' => 'second'],
        ]);

        $controller->query = Mockery::mock(BaseQuery::class);
        $controller->query->shouldReceive('getListSelectItems')
            ->once()
            ->with($request)
            ->andReturn($items);

        $response = $controller->getListSelect($request);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertCount(2, $data['data']);
        $this->assertSame('first', $data['data'][0]['label']);
    }

    public function test_get_list_select_uses_default_select_item_resource(): void
    {
        $controller = new class extends BaseController {
            use SelectItemControllerTrait;

            public BaseQuery $query;
        };

        $request = new class extends \App\Http\Requests\SelectItemRequest {
            protected string $model = SelectItemControllerFakeModel::class;
        };
        $request->replace(['key' => 'id', 'name' => ['name']]);

        $controller->query = Mockery::mock(BaseQuery::class);
        $controller->query->shouldReceive('getListSelectItems')
            ->once()
            ->with($request)
            ->andReturn(collect([
                ['id' => 10, 'name' => 'Ten'],
            ]));

        $response = $controller->getListSelect($request);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertSame(10, $data['data'][0]['key']);
        $this->assertSame('Ten', $data['data'][0]['name']);

        $method = new \ReflectionMethod($controller, 'selectItemResourceClass');
        $method->setAccessible(true);
        $this->assertSame(SelectItemResource::class, $method->invoke($controller));
    }
}

class SelectItemControllerFakeModel extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = ['name'];

    public $timestamps = false;
}

class SelectItemControllerResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'value' => $this['value'] ?? null,
            'label' => $this['label'] ?? null,
        ];
    }
}
