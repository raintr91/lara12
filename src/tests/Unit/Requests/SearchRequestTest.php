<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\SearchRequest;
use ReflectionMethod;
use Tests\Unit\UnitTestCase;

class SearchRequestTest extends UnitTestCase
{
    private function runPrepareForValidation(SearchRequest $request): void
    {
        $method = new ReflectionMethod($request, 'prepareForValidation');
        $method->invoke($request);
    }

    public function test_searchable_default_is_empty(): void
    {
        $request = new class extends SearchRequest {
        };

        $method = new ReflectionMethod(SearchRequest::class, 'searchable');
        $this->assertSame([], $method->invoke($request));
    }

    public function test_non_array_filter_is_normalized_to_empty_before_merge(): void
    {
        $request = new class extends SearchRequest {
            protected function searchable(): array
            {
                return ['name'];
            }
        };

        $request->replace(['name' => 'X', 'filter' => 'invalid']);

        $this->runPrepareForValidation($request);

        $this->assertSame(['name' => 'X'], $request->input('filter'));
    }

    public function test_non_string_non_array_filter_is_discarded_without_merge(): void
    {
        $request = new class extends SearchRequest {
        };

        $request->replace(['filter' => 123]);

        $this->runPrepareForValidation($request);

        $this->assertSame(123, $request->input('filter'));
    }

    public function test_json_string_filter_is_decoded_before_merge(): void
    {
        $request = new class extends SearchRequest {
        };

        $request->replace(['filter' => '{"name":"Hotel465","activate_status":4}']);

        $this->runPrepareForValidation($request);

        $this->assertSame([
            'name' => 'Hotel465',
            'activate_status' => 4,
        ], $request->input('filter'));
    }

    public function test_empty_filter_after_merge_is_not_merged(): void
    {
        $request = new class extends SearchRequest {
            protected function searchable(): array
            {
                return ['name'];
            }
        };

        $request->replace(['name' => '']);

        $this->runPrepareForValidation($request);

        $this->assertNull($request->input('filter'));
    }

    public function test_searchable_merges_request_params_into_filter(): void
    {
        $request = new class extends SearchRequest {
            protected function searchable(): array
            {
                return [
                    'name',
                    'status',
                    'email' => 'search',
                ];
            }
        };

        $request->replace([
            'name' => 'Japan',
            'status' => 1,
            'search' => 'user@test.com',
        ]);

        $this->runPrepareForValidation($request);

        $this->assertSame([
            'name' => 'Japan',
            'status' => 1,
            'email' => 'user@test.com',
        ], $request->input('filter'));
    }

    public function test_searchable_does_not_override_existing_filter_keys(): void
    {
        $request = new class extends SearchRequest {
            protected function searchable(): array
            {
                return ['name'];
            }
        };

        $request->replace([
            'name' => 'FromTopLevel',
            'filter' => ['name' => 'FromFilter'],
        ]);

        $this->runPrepareForValidation($request);

        $this->assertSame('FromFilter', $request->input('filter.name'));
    }

    public function test_wants_all_results_is_false_by_default(): void
    {
        $request = new class extends SearchRequest {
        };

        $this->assertFalse($request->wantsAllResults());
        $this->assertTrue($request->shouldPaginate());
    }

    /**
     * @dataProvider allFlagProvider
     */
    public function test_wants_all_results(mixed $all, bool $expected): void
    {
        $request = new class extends SearchRequest {
        };
        $request->replace(['all' => $all]);

        $this->assertSame($expected, $request->wantsAllResults());
        $this->assertSame(! $expected, $request->shouldPaginate());
    }

    public static function allFlagProvider(): array
    {
        return [
            'true' => [true, true],
            '1' => [1, true],
            'string 1' => ['1', true],
            'string true' => ['true', true],
            'false' => [false, false],
            '0' => [0, false],
            'string 0' => ['0', false],
        ];
    }

    public function test_rules_include_all_flag(): void
    {
        $request = new class extends SearchRequest {
        };

        $this->assertArrayHasKey('all', $request->rules());
    }

    public function test_searchable_skips_empty_values(): void
    {
        $request = new class extends SearchRequest {
            protected function searchable(): array
            {
                return ['name', 'status'];
            }
        };

        $request->replace([
            'name' => '',
            'status' => null,
        ]);

        $this->runPrepareForValidation($request);

        $this->assertNull($request->input('filter'));
    }

    public function test_exposes_configured_max_per_page(): void
    {
        $request = new class extends SearchRequest {
            protected int $maxPerPage = 99;
        };

        $this->assertSame(99, $request->getMaxPerPage());
    }
}
