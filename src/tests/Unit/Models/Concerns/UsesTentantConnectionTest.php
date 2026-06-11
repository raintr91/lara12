<?php

namespace Tests\Unit\Models\Concerns;

use App\Models\Concerns\UsesTentantConnection;
use Illuminate\Database\Eloquent\Model;
use Tests\Unit\UnitTestCase;

class UsesTentantConnectionTest extends UnitTestCase
{
    public function test_sets_tenant_connection_name(): void
    {
        $model = new class extends Model {
            use UsesTentantConnection;
        };

        $this->assertSame('tentant', $model->getConnectionName());
    }
}
