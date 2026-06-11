<?php

namespace Tests\Unit\Http\Resources;

use App\Http\Requests\SelectItemRequest;
use Tests\Unit\UnitTestCase;
use App\Http\Resources\SelectItemResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class SelectItemResourceTest extends UnitTestCase
{
    public function test_resource_builds_single_name_field_from_string_param(): void
    {
        $item = new SelectItemResourceItemModel();
        $item->setAttribute('id', 12);
        $item->setAttribute('name', 'Item C');

        $request = new Request([
            'key' => 'id',
            'name' => 'name',
        ]);

        $data = (new SelectItemResource($item))->toArray($request);

        $this->assertSame(12, $data['key']);
        $this->assertSame('Item C', $data['name']);
        $this->assertSame([], $data['info']);
    }

    public function test_resource_uses_select_item_request_name_fields(): void
    {
        $item = new SelectItemResourceItemModel();
        $item->setAttribute('id', 13);
        $item->setAttribute('code', 'I-02');
        $item->setAttribute('name', 'Item D');

        $request = new class extends SelectItemRequest {
            protected string $model = SelectItemResourceItemModel::class;
        };
        $request->replace([
            'key' => 'id',
            'name' => 'code',
        ]);

        $data = (new SelectItemResource($item))->toArray($request);

        $this->assertSame(13, $data['key']);
        $this->assertSame('I-02', $data['name']);
    }

    public function test_resource_builds_string_info_param(): void
    {
        $item = new SelectItemResourceItemModel();
        $item->setAttribute('id', 14);
        $item->setAttribute('name', 'Item E');
        $item->setAttribute('type', 'demo');

        $request = new Request([
            'key' => 'id',
            'name' => 'name',
            'info' => 'type',
        ]);

        $data = (new SelectItemResource($item))->toArray($request);

        $this->assertSame('Item E', $data['name']);
        $this->assertSame('demo', $data['info']['type']);
    }

    public function test_resource_builds_key_name_and_info_with_multiple_name_fields(): void
    {
        $item = new SelectItemResourceItemModel();
        $item->setAttribute('id', 10);
        $item->setAttribute('code', 'I-01');
        $item->setAttribute('name', 'Item A');
        $item->setAttribute('type', 'demo');

        $request = new Request([
            'key' => 'id',
            'name' => ['code', 'name'],
            'info' => ['type'],
        ]);

        $data = (new SelectItemResource($item))->toArray($request);

        $this->assertSame(10, $data['key']);
        $this->assertSame('I-01 Item A', $data['name']);

        // When multiple name fields are used, remaining fields are put into info.
        $this->assertArrayHasKey('name', $data['info']);
        $this->assertSame('Item A', $data['info']['name']);

        // Extra info fields
        $this->assertSame('demo', $data['info']['type']);
    }

    public function test_resource_supports_dotted_relation_info(): void
    {
        $company = new SelectItemResourceCompanyModel();
        $company->setAttribute('id', 5);
        $company->setAttribute('name', 'ACME');

        $item = new SelectItemResourceItemModel();
        $item->setAttribute('id', 11);
        $item->setAttribute('name', 'Item B');
        $item->setRelation('company', $company);

        $request = new Request([
            'key' => 'id',
            'name' => ['name'],
            'info' => ['company.name'],
        ]);

        $data = (new SelectItemResource($item))->toArray($request);

        $this->assertSame(11, $data['key']);
        $this->assertSame('Item B', $data['name']);
        $this->assertSame(['company' => ['name' => 'ACME']], $data['info']);
    }
}

class SelectItemResourceItemModel extends Model
{
    protected $fillable = ['code', 'name', 'type'];

    public $timestamps = false;

    public function company()
    {
        return $this->belongsTo(SelectItemResourceCompanyModel::class);
    }
}

class SelectItemResourceCompanyModel extends Model
{
    protected $fillable = ['name'];

    public $timestamps = false;
}
