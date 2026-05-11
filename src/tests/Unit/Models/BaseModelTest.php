<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use Tests\Unit\Models\Concerns\HasModelContractAssertions;

abstract class BaseModelTest extends TestCase
{
    use HasModelContractAssertions;
}
