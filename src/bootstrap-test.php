<?php

/**
 * Bootstrap file for PHPUnit tests
 * Configures PCOV before any code is loaded
 */

// Configure PCOV before loading composer autoloader
// This ensures code coverage works regardless of environment
if (extension_loaded('pcov')) {
    $projectRoot = __DIR__;
    ini_set('pcov.enabled', '1');
    ini_set('pcov.directory', $projectRoot . '/app,' . $projectRoot . '/Modules');
    ini_set('pcov.exclude', '~vendor~,~Tests~');
}

// Load composer autoloader
require_once __DIR__ . '/vendor/autoload.php';
