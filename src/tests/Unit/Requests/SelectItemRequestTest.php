<?php

namespace Tests\Unit\Requests;

use Tests\TestCase;
use App\Http\Requests\SelectItemRequest;
use Illuminate\Database\Eloquent\Model;

class SelectItemRequestTest extends TestCase
{
    private function makeRequest(array $input = []): SelectItemRequest
    {
        $request = new class extends SelectItemRequest {
            protected string $model = SelectItemRequestFakeModel::class;
        };

        // FormRequest extends Symfony Request; replace sets input for input()/all().
        $request->replace($input);

        return $request;
    }

    public function test_allowed_scalar_fields_includes_primary_key_and_fillable(): void
    {
        $request = $this->makeRequest();

        $allowed = $request->allowedScalarFields();

        $this->assertContains('id', $allowed);
        $this->assertContains('code', $allowed);
        $this->assertContains('name', $allowed);
    }

    public function test_name_fields_filters_invalid_values(): void
    {
        $request = $this->makeRequest([
            'name' => ['code', '', null, 123, 'name'],
        ]);

        $this->assertSame(['code', 'name'], $request->nameFields());
    }

    public function test_info_fields_filters_invalid_values(): void
    {
        $request = $this->makeRequest([
            'info' => ['status', '', null, 0, 'company.name'],
        ]);

        $this->assertSame(['status', 'company.name'], $request->infoFields());
    }

    public function test_key_field_reads_input(): void
    {
        $request = $this->makeRequest([
            'key' => 'id',
        ]);

        $this->assertSame('id', $request->keyField());
    }

    public function test_name_and_info_return_empty_when_not_array(): void
    {
        $request = $this->makeRequest([
            'name' => 'name',
            'info' => 'status',
        ]);

        $this->assertSame([], $request->nameFields());
        $this->assertSame([], $request->infoFields());
    }

    public function test_model_instance_returns_model_object(): void
    {
        $request = $this->makeRequest();
        $this->assertInstanceOf(SelectItemRequestFakeModel::class, $request->modelInstance());
    }
}

class SelectItemRequestFakeModel extends Model
{
    protected $fillable = ['code', 'name', 'status'];

    public $timestamps = false;
}
