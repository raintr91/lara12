<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\SelectItemRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Tests\Unit\UnitTestCase;

class SelectItemRequestTest extends UnitTestCase
{
    private function makeRequest(array $input = []): SelectItemRequest
    {
        $request = new class extends SelectItemRequest {
            protected string $model = SelectItemRequestFakeModel::class;
        };
        $request->replace($input);

        return $request;
    }

    public function test_model_class_and_instance(): void
    {
        $request = $this->makeRequest();
        $this->assertSame(SelectItemRequestFakeModel::class, $request->modelClass());
        $this->assertInstanceOf(SelectItemRequestFakeModel::class, $request->modelInstance());
    }

    public function test_model_class_throws_when_not_configured(): void
    {
        $request = new class extends SelectItemRequest {
        };

        $this->expectException(\RuntimeException::class);
        $request->modelClass();
    }

    public function test_allowed_scalar_fields_includes_primary_key_and_fillable(): void
    {
        $allowed = $this->makeRequest()->allowedScalarFields();

        $this->assertContains('id', $allowed);
        $this->assertContains('code', $allowed);
        $this->assertContains('name', $allowed);
    }

    public function test_key_field_reads_input(): void
    {
        $request = $this->makeRequest(['key' => 'id']);

        $this->assertSame('id', $request->keyField());
    }

    public function test_name_fields_from_array_input(): void
    {
        $request = $this->makeRequest([
            'name' => ['code', '', 'name', null, 123],
        ]);

        $this->assertSame(['code', 'name'], $request->nameFields());
    }

    public function test_name_fields_from_string_input(): void
    {
        $request = $this->makeRequest(['name' => 'code']);

        $this->assertSame(['code'], $request->nameFields());
    }

    public function test_info_fields_from_string_and_array_input(): void
    {
        $request = $this->makeRequest([
            'info' => ['status', '', 'company.name'],
        ]);
        $this->assertSame(['status', 'company.name'], $request->infoFields());

        $request = $this->makeRequest(['info' => 'status']);
        $this->assertSame(['status'], $request->infoFields());
    }

    public function test_name_and_info_return_empty_when_invalid(): void
    {
        $request = $this->makeRequest(['name' => '', 'info' => 1]);

        $this->assertSame([], $request->nameFields());
        $this->assertSame([], $request->infoFields());
    }

    public function test_normalize_field_list_static_helper(): void
    {
        $this->assertSame(['full_name'], SelectItemRequest::normalizeFieldList('full_name'));
        $this->assertSame(['code', 'name'], SelectItemRequest::normalizeFieldList(['code', 'name']));
        $this->assertSame([], SelectItemRequest::normalizeFieldList(''));
        $this->assertSame([], SelectItemRequest::normalizeFieldList(null));
        $this->assertSame([], SelectItemRequest::normalizeFieldList(123));
    }

    public function test_rules_accept_valid_array_payload(): void
    {
        $request = $this->makeRequest();

        $validator = Validator::make([
            'key' => 'id',
            'name' => ['name'],
            'info' => ['status', 'company.name'],
            'filter' => ['status' => 1],
            'page' => 1,
            'per_page' => 15,
        ], $request->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_rules_accept_string_name_query_param(): void
    {
        $request = $this->makeRequest();

        $validator = Validator::make([
            'key' => 'id',
            'name' => 'name',
        ], $request->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_rules_accept_string_info_query_param(): void
    {
        $request = $this->makeRequest();

        $validator = Validator::make([
            'key' => 'id',
            'name' => ['name'],
            'info' => 'status',
        ], $request->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_rules_accept_multiple_name_fields_as_array(): void
    {
        $request = $this->makeRequest();

        $validator = Validator::make([
            'key' => 'id',
            'name' => ['code', 'name'],
        ], $request->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_rules_reject_invalid_key_and_array_name(): void
    {
        $request = $this->makeRequest();

        $validator = Validator::make([
            'key' => 'not_allowed',
            'name' => ['unknown_field'],
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('key', $validator->errors()->toArray());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_rules_reject_invalid_string_name(): void
    {
        $request = $this->makeRequest();

        $validator = Validator::make([
            'key' => 'id',
            'name' => 'unknown_field',
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_rules_reject_empty_string_name(): void
    {
        $request = $this->makeRequest();

        $validator = Validator::make([
            'key' => 'id',
            'name' => '',
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_rules_reject_non_string_non_array_name(): void
    {
        $request = $this->makeRequest();

        $validator = Validator::make([
            'key' => 'id',
            'name' => 123,
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_rules_reject_empty_name_array(): void
    {
        $request = $this->makeRequest();

        $validator = Validator::make([
            'key' => 'id',
            'name' => [],
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }
}

class SelectItemRequestFakeModel extends Model
{
    protected $fillable = ['code', 'name', 'status'];

    public $timestamps = false;
}
