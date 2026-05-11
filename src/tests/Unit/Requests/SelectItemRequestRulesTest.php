<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\SelectItemRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SelectItemRequestRulesTest extends TestCase
{
    private function makeRequest(): SelectItemRequest
    {
        return new class extends SelectItemRequest {
            protected string $model = SelectItemRulesFakeModel::class;
        };
    }

    public function test_rules_accept_valid_payload(): void
    {
        $request = $this->makeRequest();

        $data = [
            'key' => 'id',
            'name' => ['name'],
            'info' => ['status', 'company.name'],
            'filter' => ['status' => 1],
            'page' => 1,
            'per_page' => 15,
        ];

        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_rules_reject_invalid_key_and_name(): void
    {
        $request = $this->makeRequest();

        $data = [
            'key' => 'not_allowed',
            'name' => ['unknown_field'],
        ];

        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('key', $validator->errors()->toArray());
        $this->assertArrayHasKey('name.0', $validator->errors()->toArray());
    }

    public function test_model_class_throws_if_not_configured(): void
    {
        $request = new class extends SelectItemRequest {
        };

        $this->expectException(\RuntimeException::class);
        $request->modelClass();
    }
}

class SelectItemRulesFakeModel extends Model
{
    protected $fillable = ['name', 'status'];

    public $timestamps = false;
}
