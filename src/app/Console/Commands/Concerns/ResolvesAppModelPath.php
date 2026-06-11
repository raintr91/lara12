<?php

namespace App\Console\Commands\Concerns;

use Illuminate\Support\Str;

trait ResolvesAppModelPath
{
    /**
     * @return array{
     *     modelClass: string,
     *     pathSegments: list<string>,
     *     modelNamespace: string,
     *     modelFqcn: string,
     *     modelPath: string,
     *     factoryNamespace: string,
     *     factoryPath: string,
     *     factoryClass: string,
     *     seederNamespace: string,
     *     seederPath: string,
     *     seederClass: string,
     *     testNamespace: string,
     *     testPath: string,
     *     testClass: string,
     *     migrationPath: string,
     *     table: string,
     *     baseClass: string,
     *     factoryBaseClass: string,
     *     factoryBaseUse: string
     * }
     */
    protected function resolveAppModelTarget(string $name, ?string $path = null): array
    {
        if (str_contains($name, '/') || str_contains($name, '\\')) {
            throw new \InvalidArgumentException(
                'Model name must not contain a path. Use: php artisan m:model Chain Platform'
            );
        }

        $modelClass = $this->normalizeModelClassName(trim($name));
        $pathSegments = $this->normalizePathSegments($path);

        $modelNamespace = 'App\\Models'.($pathSegments === [] ? '' : '\\'.implode('\\', $pathSegments));
        $modelFqcn = $modelNamespace.'\\'.$modelClass;
        $relativePath = $pathSegments === []
            ? "{$modelClass}.php"
            : implode('/', $pathSegments).'/'.$modelClass.'.php';

        $factoryClass = $modelClass.'Factory';
        $factoryNamespace = 'Database\\Factories'.($pathSegments === [] ? '' : '\\'.implode('\\', $pathSegments));
        $factoryRelative = $pathSegments === []
            ? "{$factoryClass}.php"
            : implode('/', $pathSegments).'/'.$factoryClass.'.php';

        $seederClass = $modelClass.'Seeder';
        $seederNamespace = 'Database\\Seeders'.($pathSegments === [] ? '' : '\\'.implode('\\', $pathSegments));
        $seederRelative = $pathSegments === []
            ? "{$seederClass}.php"
            : implode('/', $pathSegments).'/'.$seederClass.'.php';

        $testClass = $modelClass.'ModelTest';
        $testNamespace = 'Tests\\Unit\\Models'.($pathSegments === [] ? '' : '\\'.implode('\\', $pathSegments));
        $testRelative = $pathSegments === []
            ? "{$testClass}.php"
            : implode('/', $pathSegments).'/'.$testClass.'.php';

        $migrationFolder = $pathSegments === []
            ? ''
            : Str::lower(implode('/', $pathSegments));

        [$baseClass, $baseUse, $factoryBaseClass, $factoryBaseUse] = $this->resolveModelScaffoldDefaults($pathSegments);

        return [
            'modelClass' => $modelClass,
            'pathSegments' => $pathSegments,
            'modelNamespace' => $modelNamespace,
            'modelFqcn' => $modelFqcn,
            'modelPath' => app_path('Models/'.$relativePath),
            'factoryNamespace' => $factoryNamespace,
            'factoryPath' => database_path('factories/'.$factoryRelative),
            'factoryClass' => $factoryClass,
            'seederNamespace' => $seederNamespace,
            'seederPath' => database_path('seeders/'.$seederRelative),
            'seederClass' => $seederClass,
            'testNamespace' => $testNamespace,
            'testPath' => base_path('tests/Unit/Models/'.$testRelative),
            'testClass' => $testClass,
            'migrationPath' => $migrationFolder === '' ? 'database/migrations' : 'database/migrations/'.$migrationFolder,
            'table' => Str::snake(Str::pluralStudly($modelClass)),
            'baseClass' => $baseClass,
            'baseUse' => $baseUse,
            'factoryBaseClass' => $factoryBaseClass,
            'factoryBaseUse' => $factoryBaseUse,
        ];
    }

    /**
     * @return list<string>
     */
    protected function normalizePathSegments(?string $path): array
    {
        if ($path === null || trim($path) === '') {
            return [];
        }

        $normalized = str_replace('\\', '/', trim($path));

        $segments = array_values(array_filter(
            array_map(fn (string $p) => Str::studly($p), explode('/', $normalized)),
            fn (string $p) => $p !== ''
        ));

        if (($segments[0] ?? '') === 'Control') {
            $segments[0] = 'Platform';
        }
        if (($segments[0] ?? '') === 'Chain') {
            $segments[0] = 'Tenant';
        }

        return $segments;
    }

    protected function normalizeModelClassName(string $name): string
    {
        $name = Str::studly($name);

        if (Str::endsWith($name, 'Model') && strlen($name) > 5) {
            return Str::beforeLast($name, 'Model');
        }

        return $name;
    }

    /**
     * @param  list<string>  $pathSegments
     * @return array{0: string, 1: string, 2: string, 3: string}
     */
    protected function resolveModelScaffoldDefaults(array $pathSegments): array
    {
        $root = $pathSegments[0] ?? '';

        return match ($root) {
            'Control' => ['PlatformModel', "use App\Models\PlatformModel;\n\n", 'BaseModelFactory', "use Database\Factories\BaseModelFactory;\n"],
            'Chain' => ['TenantModel', "use App\Models\TenantModel;\n\n", 'BaseModelFactory', "use Database\Factories\BaseModelFactory;\n"],
            'Platform' => ['PlatformModel', "use App\Models\PlatformModel;\n\n", 'BaseModelFactory', "use Database\Factories\BaseModelFactory;\n"],
            'Tenant' => ['TenantModel', "use App\Models\TenantModel;\n\n", 'BaseModelFactory', "use Database\Factories\BaseModelFactory;\n"],
            default => ['BaseModel', "use App\Models\BaseModel;\n\n", 'BaseModelFactory', "use Database\Factories\BaseModelFactory;\n"],
        };
    }
}
