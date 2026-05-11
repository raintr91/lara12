<?php

namespace Tests\Unit\Providers;

use App\Providers\ModuleProvider;
use Tests\TestCase;

class ModuleProviderTest extends TestCase
{
    public function test_register_does_not_throw(): void
    {
        // ModuleProvider::register() is a no-op placeholder when no modules register here.
        try {
            ModuleProvider::register();
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail("ModuleProvider::register() threw an exception: {$e->getMessage()}");
        }
    }

    public function test_boot_does_not_throw(): void
    {
        // ModuleProvider::boot() is a no-op placeholder when no modules boot here.
        try {
            ModuleProvider::boot();
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail("ModuleProvider::boot() threw an exception: {$e->getMessage()}");
        }
    }

    public function test_register_and_boot_sequence(): void
    {
        try {
            ModuleProvider::register();
            ModuleProvider::boot();
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail("ModuleProvider sequence threw an exception: {$e->getMessage()}");
        }
    }
}
