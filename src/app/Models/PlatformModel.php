<?php

namespace App\Models;

use App\Models\Concerns\UsesPlatformConnection;

/**
 * Platform / control-plane models (`saas-mairy` connection).
 */
abstract class PlatformModel extends BaseModel
{
    use UsesPlatformConnection;
}
