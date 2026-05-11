<?php

namespace Tests\Unit\Http\Controllers\Traits;

use App\Http\Controllers\BaseController;
use App\Http\Controllers\Traits\SelectItemControllerTrait;
use App\Http\Requests\SelectItemRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use RuntimeException;
use Tests\TestCase;

class SelectItemControllerTraitTest extends TestCase
{
    public function test_get_list_select_returns_success_payload(): void
    {
        $controller = new class extends BaseController {
            use SelectItemControllerTrait;

            protected function selectItemQueryClass(): string
            {
                return SelectItemControllerQueryWithMethod::class;
            }

            protected function selectItemResourceClass(): string
            {
                return SelectItemControllerResource::class;
            }
        };

        $request = new class extends SelectItemRequest {
            protected string $model = SelectItemControllerDummyModel::class;
        };

        $response = $controller->getListSelect($request);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertSame('Retrieved successfully', $data['message']);
        $this->assertCount(2, $data['data']);
        $this->assertSame('first', $data['data'][0]['label']);
    }

    public function test_get_list_select_throws_when_query_does_not_define_method(): void
    {
        $controller = new class extends BaseController {
            use SelectItemControllerTrait;

            protected function selectItemQueryClass(): string
            {
                return SelectItemControllerQueryWithoutMethod::class;
            }
        };

        $request = new class extends SelectItemRequest {
            protected string $model = SelectItemControllerDummyModel::class;
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('missing method getListSelectItems()');

        $controller->getListSelect($request);
    }
}

class SelectItemControllerDummyModel extends Model
{
    protected $fillable = ['name'];
}

class SelectItemControllerQueryWithMethod
{
    public function __construct(private SelectItemRequest $request)
    {
    }

    public function getListSelectItems(): Collection
    {
        return collect([
            ['value' => 1, 'label' => 'first'],
            ['value' => 2, 'label' => 'second'],
        ]);
    }
}

class SelectItemControllerQueryWithoutMethod
{
    public function __construct(private SelectItemRequest $request)
    {
    }
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
