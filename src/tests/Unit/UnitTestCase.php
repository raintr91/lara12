<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/** Unit tests that do not need database migrations. */
abstract class UnitTestCase extends BaseTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        putenv('APP_ENV=testing');
        $_ENV['APP_ENV'] = 'testing';
        $_SERVER['APP_ENV'] = 'testing';
    }
}
