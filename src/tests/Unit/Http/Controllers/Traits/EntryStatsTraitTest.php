<?php

namespace Tests\Unit\Http\Controllers\Traits;

use App\Http\Controllers\BaseController;
use App\Http\Controllers\Traits\EntryStatsTrait;
use Illuminate\Http\JsonResponse;
use Tests\Unit\UnitTestCase;

class EntryStatsTraitTest extends UnitTestCase
{
    public function test_returns_success_payload(): void
    {
        $controller = new class extends BaseController {
            use EntryStatsTrait;

            public object $query;
        };

        $controller->query = new class {
            public function stats(): array
            {
                return ['hotels' => 7];
            }
        };

        $response = $controller->stats();
        $data = json_decode($response->getContent(), true);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertTrue($data['success']);
        $this->assertSame(['hotels' => 7], $data['data']);
    }

    public function test_returns_error_when_stats_fails(): void
    {
        $controller = new class extends BaseController {
            use EntryStatsTrait;

            public object $query;
        };

        $controller->query = new class {
            public function stats(): never
            {
                throw new \RuntimeException('stats failed');
            }
        };

        $response = $controller->stats();
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertSame('stats failed', $data['message']);
    }
}
