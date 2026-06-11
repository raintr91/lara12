<?php

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\Controller;
use Tests\Unit\UnitTestCase;

class ControllerTest extends UnitTestCase
{
    public function test_can_be_extended(): void
    {
        $controller = new class extends Controller {};

        $this->assertInstanceOf(Controller::class, $controller);
    }
}
