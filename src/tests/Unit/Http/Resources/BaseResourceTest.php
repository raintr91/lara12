<?php

namespace Tests\Unit\Http\Resources;

use Tests\Unit\UnitTestCase;
use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class TestModel
{
    public function __construct(
        public mixed $id = null,
        public ?string $name = null,
        public ?string $email = null,
    ) {
    }

    public mixed $created_at = null;
    public mixed $updated_at = null;

    public function getKey(): mixed
    {
        return $this->id;
    }
}

class TestResource extends BaseResource
{
    protected function fields(Request $request): array
    {
        return [
            'name' => $this->resource?->name,
            'email' => $this->resource?->email,
        ];
    }
}

class RelationChildResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id' => $this->resource->id];
    }
}

class RelationModel extends Model
{
    protected $guarded = [];
    public $timestamps = false;
}

class ExtendedResource extends TestResource
{
    public function callCollectionRelation(string $relation, string $resourceClass)
    {
        return $this->collectionRelation($relation, $resourceClass);
    }

    public function callItem(string $relation, string $resourceClass)
    {
        return $this->item($relation, $resourceClass);
    }

    public function callRename(string $key, mixed $value): array
    {
        return $this->rename($key, $value);
    }

    public function callSnake(string $key): string
    {
        return $this->snake($key);
    }
}

class BaseResourceTest extends UnitTestCase
{
    /**
     * Test BaseResource can transform model.
     */
    public function test_base_resource_to_array(): void
    {
        $resource = new TestResource(new TestModel(
            id: 1,
            name: 'John Doe',
            email: 'john@example.com'
        ));

        $result = $resource->resolve(new Request());

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['id']);
        $this->assertEquals('John Doe', $result['name']);
    }

    /**
     * Test BaseResource collection transformation.
     */
    public function test_base_resource_collection(): void
    {
        $items = [
            new TestModel(id: 1, name: 'John'),
            new TestModel(id: 2, name: 'Jane'),
        ];

        $collection = TestResource::collection($items);
        $resolved = $collection->resolve(new Request());

        $this->assertCount(2, $resolved);
        $this->assertSame(1, $resolved[0]['id']);
        $this->assertSame('John', $resolved[0]['name']);
    }

    /**
     * Test BaseResource with empty data.
     */
    public function test_base_resource_with_empty_data(): void
    {
        $resource = new TestResource(new TestModel());
        $result = $resource->resolve(new Request());

        $this->assertIsArray($result);
    }

    public function test_timestamps_and_helper_methods(): void
    {
        $model = new RelationModel();
        $model->id = 99;
        $model->name = 'Parent';
        $model->email = null;
        $model->created_at = Carbon::parse('2026-01-01 00:00:00');
        $model->updated_at = Carbon::parse('2026-01-02 00:00:00');

        $model->setRelation('children', collect([(object) ['id' => 10]]));
        $model->setRelation('owner', (object) ['id' => 11]);

        $resource = new ExtendedResource($model);
        $resolved = $resource->resolve(new Request());

        $this->assertArrayHasKey('created_at', $resolved);
        $this->assertArrayHasKey('updated_at', $resolved);
        $this->assertSame(['x_key' => 1], $resource->callRename('x_key', 1));
        $this->assertSame('sample_key', $resource->callSnake('sampleKey'));

        $collectionRelation = $resource->callCollectionRelation('children', RelationChildResource::class);
        $itemRelation = $resource->callItem('owner', RelationChildResource::class);

        $this->assertNotNull($collectionRelation);
        $this->assertNotNull($itemRelation);
    }
}
