<?php

namespace Tests\Unit\Models\Concerns;

use App\Models\Concerns\UsesPlatformConnection;
use Illuminate\Database\Eloquent\Model;
use Tests\Unit\UnitTestCase;

class UsesPlatformConnectionTest extends UnitTestCase
{
    public function test_sets_platform_connection_name(): void
    {
        $model = new class extends Model {
            use UsesPlatformConnection;
        };

        $this->assertSame('platform', $model->getConnectionName());
    }
}
