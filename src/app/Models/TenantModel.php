<?php

namespace App\Models;

use App\Models\Concerns\UsesTentantConnection;

/**
 * Per-chain tenant models (`saas_mairy_chain_{id}` connection).
 */
abstract class TenantModel extends BaseModel
{
    use UsesTentantConnection;
}
