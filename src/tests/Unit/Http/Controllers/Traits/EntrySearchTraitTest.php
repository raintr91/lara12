<?php

namespace Tests\Unit\Http\Controllers\Traits;

use App\Http\Controllers\BaseController;
use App\Http\Controllers\Traits\EntrySearchTrait;
use Tests\Unit\UnitTestCase;

class EntrySearchTraitTest extends UnitTestCase
{
    public function test_returns_error_when_query_missing(): void
    {
        $controller = new class extends BaseController {
            use EntrySearchTrait;
        };

        $response = $controller->search();
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertSame('Query is not configured for search.', $data['message']);
    }

    public function test_returns_plain_collection_payload(): void
    {
        $controller = new class extends BaseController {
            use EntrySearchTrait;

            public function listPlain(mixed $data): \Illuminate\Http\JsonResponse
            {
                return $this->paginatedQueryResponse($data, 'ok');
            }
        };

        $response = $controller->listPlain(collect([['id' => 1]]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([['id' => 1]], $data['data']);
    }
}
