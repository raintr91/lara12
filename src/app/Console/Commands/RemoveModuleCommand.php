<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class RemoveModuleCommand extends Command
{
    protected $signature = 'rm:module {name : Module name (StudlyCase)}';
    protected $description = 'Remove a module directory and unregister its provider from ModuleProvider.';

    public function handle(): int
    {
        $name = (string) $this->argument('name');
        $modulePath = base_path('Modules/'.$name);
        $providerFqn = 'Modules\\'.$name.'\\Providers\\'.$name.'ServiceProvider';
        $providerPlainLine = $providerFqn;
        $providerRegisterLine = 'app()->register(\\'.$providerFqn.'::class)';
        $files = $this->laravel['files'];

        // Remove module directory
        if ($files->isDirectory($modulePath)) {
            $files->deleteDirectory($modulePath);
            $this->info("Deleted module directory: {$modulePath}");
        } else {
            $this->warn("Module directory not found: {$modulePath}");
        }

        // Remove provider registration from ModuleProvider.php (boot and register)
        $moduleProviderPath = base_path('app/Providers/ModuleProvider.php');
        if ($files->exists($moduleProviderPath)) {
            $contents = $files->get($moduleProviderPath);
            // Remove all occurrences (old and new formats) with optional semicolon and whitespace.
            $patterns = [
                '/^[ \t]*'.preg_quote($providerPlainLine, '/').';?[ \t]*\r?\n/m',
                '/^[ \t]*'.preg_quote($providerRegisterLine, '/').';?[ \t]*\r?\n/m',
            ];
            $updated = preg_replace($patterns, '', $contents);
            if ($updated !== null) {
                $files->put($moduleProviderPath, $updated);
                $this->info("Unregistered provider from ModuleProvider: {$providerFqn}");
            } else {
                $this->warn("Could not update ModuleProvider.php. Please remove provider manually if needed.");
            }
        } else {
            $this->warn("ModuleProvider.php not found: {$moduleProviderPath}");
        }

        $this->removePhpUnitTestsuite($files, $name);

        return 0;
    }

    private function removePhpUnitTestsuite(Filesystem $files, string $module): void
    {
        $phpunitPath = base_path('phpunit.xml');
        if (! $files->exists($phpunitPath)) {
            $this->warn("phpunit.xml not found: {$phpunitPath}");
            return;
        }

        $contents = $files->get($phpunitPath);
        $changed = false;

        // Remove testsuite entry
        $suiteName = 'Module'.$module;
        $suitePattern = '/\n\s*<testsuite name="'.preg_quote($suiteName, '/').'">\n\s*<directory suffix="Test\\.php">'.preg_quote('Modules/'.$module.'/Tests', '/').'<\/directory>\n\s*<\/testsuite>/m';
        $updated = preg_replace($suitePattern, '', $contents);
        if ($updated !== null && $updated !== $contents) {
            $contents = $updated;
            $changed = true;
            $this->info("Removed PHPUnit testsuite: {$suiteName}");
        }

        // Remove coverage exclude entries for Resources/Lang and Resources/Views
        $excludePatterns = [
            '/\n[ \t]*<directory suffix="\.php">'.preg_quote('Modules/'.$module.'/Resources/Lang', '/').'<\/directory>/m',
            '/\n[ \t]*<directory suffix="\.php">'.preg_quote('Modules/'.$module.'/Resources/Views', '/').'<\/directory>/m',
        ];
        foreach ($excludePatterns as $pattern) {
            $updated = preg_replace($pattern, '', $contents);
            if ($updated !== null && $updated !== $contents) {
                $contents = $updated;
                $changed = true;
            }
        }
        if ($changed) {
            $files->put($phpunitPath, $contents);
        }
        if ($changed) {
            $this->info("Removed coverage excludes for Modules/{$module}/Resources/(Lang|Views)");
        }
    }
}
