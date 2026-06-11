<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\BulkDeleteRequest;
use Illuminate\Support\Facades\Validator;
use Tests\Unit\UnitTestCase;

class BulkDeleteRequestTest extends UnitTestCase
{
    public function test_rules_require_non_empty_integer_ids(): void
    {
        $request = $this->makeRequest();

        $validator = Validator::make(['ids' => [1, 2, 3]], $request->rules());
        $this->assertFalse($validator->fails());

        $validator = Validator::make(['ids' => []], $request->rules());
        $this->assertTrue($validator->fails());

        $validator = Validator::make(['ids' => ['x']], $request->rules());
        $this->assertTrue($validator->fails());
    }

    public function test_max_ids_limit_is_applied(): void
    {
        $request = new class extends BulkDeleteRequest {
            protected int $maxIds = 2;
        };

        $validator = Validator::make(['ids' => [1, 2, 3]], $request->rules());
        $this->assertTrue($validator->fails());
    }

    public function test_exposes_configured_max_ids(): void
    {
        $request = new class extends BulkDeleteRequest {
            protected int $maxIds = 42;
        };

        $this->assertSame(42, $request->getMaxIds());
    }

    private function makeRequest(): BulkDeleteRequest
    {
        return new class extends BulkDeleteRequest {
        };
    }
}
