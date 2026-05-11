<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProjectTestSuiteConfigurationTest extends TestCase
{
    public function test_phpunit_config_defines_root_testsuite(): void
    {
        $xml = file_get_contents(base_path('phpunit.xml'));
        $this->assertIsString($xml);

        $this->assertStringContainsString('testsuite name="Root"', $xml);
    }

    public function test_phpunit_coverage_source_includes_app_and_modules(): void
    {
        $xml = file_get_contents(base_path('phpunit.xml'));
        $this->assertIsString($xml);

        // Chấp nhận cả dạng có suffix và không suffix
        $this->assertMatchesRegularExpression('/<directory( [^>]*)?>app<\\/directory>/', $xml);
        $this->assertMatchesRegularExpression('/<directory( [^>]*)?>Modules<\\/directory>/', $xml);
    }
}
