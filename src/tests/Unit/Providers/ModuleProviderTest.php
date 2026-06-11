<?php

namespace Tests\Unit\Providers;

use App\Providers\ModuleProvider;
use Tests\Unit\UnitTestCase;

class ModuleProviderTest extends UnitTestCase
{
    public function test_register_registers_hook_service_provider(): void
    {
        // ModuleProvider::register() should register Hook and Chain providers
        // We can't directly test this without mocking, so we'll test that it doesn't throw
        try {
            ModuleProvider::register();
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail("ModuleProvider::register() threw an exception: {$e->getMessage()}");
        }
    }

    public function test_boot_boots_hook_service_provider(): void
    {
        // ModuleProvider::boot() should boot Hook and Chain providers
        // We can't directly test this without mocking, so we'll test that it doesn't throw
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
