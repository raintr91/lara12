<?php

namespace App\Console\Commands\Concerns;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

trait GeneratesModuleFiles
{
    protected function studly(string $value): string
    {
        return Str::studly($value);
    }

    protected function studlyWithSuffix(string $value, string $suffix): string
    {
        $value = trim($value);
        $suffix = trim($suffix);

        if ($suffix === '') {
            return $this->studly($value);
        }

        $valueLower = Str::lower($value);
        $suffixLower = Str::lower($suffix);

        if (Str::endsWith($valueLower, $suffixLower)) {
            $base = substr($value, 0, max(0, strlen($value) - strlen($suffix)));
            $base = trim($base);
            return $this->studly($base).$suffix;
        }

        return $this->studly($value).$suffix;
    }

    protected function moduleRoot(string $module): string
    {
        return base_path('Modules/'.Str::studly($module));
    }

    protected function moduleNamespace(string $module, string $suffix = ''): string
    {
        $base = config('modules.namespace', 'Modules').'\\'.Str::studly($module);
        if ($suffix === '') {
            return $base;
        }

        return $base.'\\'.trim($suffix, '\\');
    }

    protected function ensureModuleExists(Filesystem $files, string $module): bool
    {
        return $files->isDirectory($this->moduleRoot($module));
    }

    protected function renderStub(Filesystem $files, string $stubPath, array $replacements): string
    {
        $contents = $files->get($stubPath);

        foreach ($replacements as $key => $value) {
            $contents = str_replace('$'.strtoupper($key).'$', $value, $contents);
        }

        return $contents;
    }

    protected function putFile(Filesystem $files, string $path, string $contents, bool $force = false): void
    {
        $dir = dirname($path);
        if (! $files->isDirectory($dir)) {
            $files->makeDirectory($dir, 0775, true);
        }

        if ($files->exists($path) && ! $force) {
            throw new \RuntimeException("File already exists: {$path}");
        }

        $files->put($path, $contents);
    }
}
