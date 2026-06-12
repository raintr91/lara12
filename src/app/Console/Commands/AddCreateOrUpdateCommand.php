<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\AsksToOverwriteExisting;
use App\Console\Commands\Concerns\GeneratesModuleFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class AddCreateOrUpdateCommand extends BaseCommand
{
    use GeneratesModuleFiles;
    use AsksToOverwriteExisting;

    protected $aliases = ['m:add-createOrUpdate', 'm:add-create-or-update'];

    protected $signature = 'add:create-or-update
                            {module : Module name (StudlyCase, e.g. Admin)}
                            {controller : Controller name (StudlyCase, no Controller suffix, e.g. Hotel)}
                            {relationship : lowerCamelCase HasOne on parent model (e.g. serverMail)}
                            {method : lowerCamelCase method name (e.g. settingMailServer)}
                            {--force : Overwrite generated request}
                            {--yes : Force yes/overwrite for all prompt steps}
                            {--skip-questions : Use defaults without prompting}';

    protected $description = 'Add 1-1 setting endpoint: Controller -> Action -> parent::findOrFail()->relationship()->updateOrCreate().';

    public function handle(): int
    {
        /** @var Filesystem $files */
        $files = $this->laravel['files'];

        $module = Str::studly((string) $this->argument('module'));
        if (! $this->ensureModuleExists($files, $module)) {
            $this->error("Module [{$module}] does not exist. Run: php artisan m:module {$module}");

            return 1;
        }

        $controllerBase = $this->studly((string) $this->argument('controller'));
        $controllerClass = $this->studlyWithSuffix($controllerBase, 'Controller');
        $name = Str::beforeLast($controllerClass, 'Controller');
        $relationship = $this->normalizeCamelCase((string) $this->argument('relationship'), 'relationship');
        $methodName = $this->normalizeCamelCase((string) $this->argument('method'), 'method');

        if ($relationship === null || $methodName === null) {
            return 1;
        }

        $requestClass = $name . Str::studly($methodName) . 'Request';
        $routeSegment = Str::kebab($methodName);
        $routePrefix = $this->resolveRoutePrefix($name);

        $moduleRoot = $this->moduleRoot($module);
        $controllerPath = $moduleRoot . "/Http/Controllers/{$controllerClass}.php";

        if (! $files->exists($controllerPath)) {
            $this->error("Controller not found: {$controllerPath}");
            $this->line("Tạo trước: php artisan m:controller {$module} {$name}");

            return 1;
        }

        $this->printPrerequisiteHints($name, $relationship);

        $parentModelFqn = $this->resolveParentModelFqn($name);
        if ($parentModelFqn === null) {
            $this->error("Không tìm thấy parent model cho [{$name}].");
            $this->line('Bắt buộc có model parent (vd App\\Models\\Platform\\Hotel) trước khi chạy lệnh.');

            return 1;
        }

        /** @var class-string<Model> $parentModelFqn */
        $parentModel = app($parentModelFqn);
        $parentShort = class_basename($parentModelFqn);

        $relationshipMeta = $this->resolveRelationshipMeta($parentModel, $relationship);
        if ($relationshipMeta === null) {
            return 1;
        }

        $relatedModelFqn = $relationshipMeta['related_model_fqn'];
        $relatedShort = class_basename($relatedModelFqn);
        $matchKey = $this->guessMatchKey($parentModelFqn);

        $actionClass = $this->studlyWithSuffix($name, 'Action');
        $actionPath = $moduleRoot . "/Http/Actions/{$actionClass}.php";
        $requestPath = $moduleRoot . "/Http/Requests/{$requestClass}.php";

        if (! $this->ensureActionExists($files, $module, $actionClass, $actionPath)) {
            return 1;
        }

        $force = (bool) $this->option('force');

        $this->createRequest($files, $module, $requestClass, $requestPath, $relatedModelFqn, $matchKey, $force);

        $this->patchAction(
            $files,
            $actionPath,
            $methodName,
            $relationship,
            $parentShort,
            $relatedShort,
            $relatedModelFqn,
            $matchKey
        );

        $this->patchController(
            $files,
            $controllerPath,
            $module,
            $requestClass,
            $methodName
        );

        $this->patchRoutes(
            $files,
            $moduleRoot . '/Routes/api.php',
            $module,
            $name,
            $controllerClass,
            $methodName,
            $routeSegment,
            $routePrefix
        );

        $this->newLine();
        $this->info("Ready: {$controllerClass}::{$methodName}()");
        $this->line("Request: {$requestClass}");
        $this->line("Route: PUT {$routePrefix}/{id}/{$routeSegment}");
        $this->line("Flow: Controller::{$methodName}() -> {$actionClass}::{$methodName}() -> findOrFail()->{$relationship}()->updateOrCreate()");
        $this->warn("Review {$requestClass} rules — tùy chỉnh required/nullable theo nghiệp vụ.");

        return 0;
    }

    private function normalizeCamelCase(string $value, string $label): ?string
    {
        $value = trim($value);

        if ($value === '' || ! preg_match('/^[a-z][a-zA-Z0-9]*$/', $value)) {
            $this->error("{$label} phải là lowerCamelCase (vd settingMailServer, serverMail).");

            return null;
        }

        return $value;
    }

    private function resolveRoutePrefix(string $controllerName): string
    {
        return Str::kebab(Str::plural($controllerName));
    }

    private function printPrerequisiteHints(string $controllerName, string $relationship): void
    {
        $this->line('Điều kiện bắt buộc trước khi chạy:');
        $this->line("  1. Parent model (vd App\\Models\\Platform\\{$controllerName})");
        $this->line('  2. Related model + belongsTo() về parent');
        $this->line("  3. Parent model có HasOne relationship: {$relationship}()");
        $this->line('  4. Tên relationship = lowerCamelCase của related model/table');
        $this->newLine();
    }

    /**
     * @return class-string<Model>|null
     */
    private function resolveParentModelFqn(string $name): ?string
    {
        foreach (['App\\Models\\Platform\\', 'App\\Models\\', 'Modules\\Tenant\\Models\\'] as $ns) {
            $candidate = $ns . $name;
            if (class_exists($candidate) && is_subclass_of($candidate, Model::class)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param  class-string<Model>  $parentModelFqn
     */
    private function guessMatchKey(string $parentModelFqn): string
    {
        /** @var Model $instance */
        $instance = app($parentModelFqn);

        return Str::singular(Str::snake($instance->getTable())) . '_id';
    }

    /**
     * @return array{related_model_fqn: class-string<Model>}|null
     */
    private function resolveRelationshipMeta(Model $parentModel, string $relationship): ?array
    {
        if (! method_exists($parentModel, $relationship)) {
            $this->error("Không tìm thấy {$relationship}() trên " . $parentModel::class);
            $this->line('Bắt buộc khai báo relationship trên parent model:');
            $this->line("    public function {$relationship}(): HasOne");
            $this->line('    {');
            $this->line('        return $this->hasOne(RelatedModel::class);');
            $this->line('    }');

            return null;
        }

        try {
            $relation = $parentModel->{$relationship}();
        } catch (\Throwable $e) {
            $this->error("Không resolve được {$relationship}(): {$e->getMessage()}");

            return null;
        }

        if (! $relation instanceof Relation) {
            $this->error("{$relationship}() phải return Eloquent Relation.");

            return null;
        }

        if (! $relation instanceof HasOne) {
            $this->warn("{$relationship}() không phải HasOne — endpoint này dành cho setting 1-1.");
        }

        $relatedModelFqn = get_class($relation->getRelated());

        if (! class_exists($relatedModelFqn)) {
            $this->error("Related model [{$relatedModelFqn}] không tồn tại.");

            return null;
        }

        $this->line('Parent: ' . $parentModel::class);
        $this->line("Related: {$relatedModelFqn} qua {$relationship}()");

        return ['related_model_fqn' => $relatedModelFqn];
    }

    private function ensureActionExists(Filesystem $files, string $module, string $class, string $path): bool
    {
        if ($files->exists($path)) {
            return true;
        }

        if (! $this->askYesNo('yes', "Chưa có {$class}. Tạo mới?", true)) {
            return false;
        }

        $this->call('m:action', [
            'module' => $module,
            'name' => $class,
            '--yes' => $this->shouldForceYes(),
        ]);

        return $files->exists($path);
    }

    /**
     * @param  class-string<Model>  $relatedModelFqn
     */
    private function createRequest(
        Filesystem $files,
        string $module,
        string $class,
        string $path,
        string $relatedModelFqn,
        string $matchKey,
        bool $force
    ): void {
        $stubPath = base_path('stubs/modules/scaffold/module-create-or-update-request.stub');
        $baseRequestClass = $this->studlyWithSuffix($module, 'Request');
        $baseRequestFqn = $this->moduleNamespace($module, 'Http\\Requests') . '\\' . $baseRequestClass;

        $choice = $force ? ($files->exists($path) ? 'overwrite' : 'create')
            : $this->confirmCreateOrOverwrite($files, "Tạo {$class}?", $path, true);

        if (! in_array($choice, ['create', 'overwrite'], true)) {
            return;
        }

        /** @var Model $relatedModel */
        $relatedModel = app($relatedModelFqn);

        $contents = $this->renderStub($files, $stubPath, [
            'namespace' => $this->moduleNamespace($module, 'Http\\Requests'),
            'class' => $class,
            'base_import' => "use {$baseRequestFqn};",
            'base_class' => $baseRequestClass,
            'related_model' => class_basename($relatedModelFqn),
            'table' => $relatedModel->getTable(),
            'rules' => $this->buildRulesFromFillable($relatedModelFqn, $matchKey),
        ]);

        $this->putFile($files, $path, $contents, $choice === 'overwrite');
        $this->line("Generated: {$path}");
    }

    /**
     * @param  class-string<Model>  $relatedModelFqn
     */
    private function buildRulesFromFillable(string $relatedModelFqn, string $matchKey): string
    {
        /** @var Model $model */
        $model = app($relatedModelFqn);
        $skip = ['id', $matchKey, 'created_at', 'updated_at', 'deleted_at'];
        $casts = $model->getCasts();
        $rules = [];

        foreach ($model->getFillable() as $field) {
            if (in_array($field, $skip, true)) {
                continue;
            }

            $rules[] = '            ' . $this->ruleLineForField($field, $casts[$field] ?? null);
        }

        return $rules !== []
            ? implode("\n", $rules)
            : "            // TODO: thêm rules theo fillable của {$relatedModelFqn}";
    }

    private function ruleLineForField(string $field, mixed $cast): string
    {
        if ($field === 'password') {
            return "'{$field}' => ['nullable', 'string', 'max:500'], // đã mã hóa từ FE";
        }

        if (str_contains($field, 'email') || $field === 'from_address') {
            return "'{$field}' => ['nullable', 'email', 'max:255'],";
        }

        return match ($cast) {
            'integer', 'int' => "'{$field}' => ['nullable', 'integer'],",
            'boolean', 'bool' => "'{$field}' => ['nullable', 'boolean'],",
            'float', 'double', 'decimal:2', 'decimal:4' => "'{$field}' => ['nullable', 'numeric'],",
            default => "'{$field}' => ['nullable', 'string', 'max:255'],",
        };
    }

    /**
     * @param  class-string<Model>  $relatedModelFqn
     */
    private function patchAction(
        Filesystem $files,
        string $path,
        string $methodName,
        string $relationship,
        string $parentShort,
        string $relatedShort,
        string $relatedModelFqn,
        string $matchKey
    ): void {
        $contents = $files->get($path);

        if (str_contains($contents, "function {$methodName}(")) {
            $this->line("Action đã có {$methodName}(): {$path}");

            return;
        }

        if (! str_contains($contents, "use {$relatedModelFqn};")) {
            $contents = preg_replace(
                '/^namespace\s+[^;]+;\n\n/m',
                "$0use {$relatedModelFqn};\n",
                $contents,
                1
            ) ?? $contents;
        }

        $method = "\n    /**\n"
            . "     * @param  array<string, mixed>  \$data validated bởi Request (không gồm {$matchKey} — lấy từ path)\n"
            . "     */\n"
            . "    public function {$methodName}(int \$id, array \$data): {$relatedShort}\n"
            . "    {\n"
            . "        /** @var {$parentShort} \$parent */\n"
            . "        \$parent = \$this->model::query()->findOrFail(\$id);\n\n"
            . "        unset(\$data['{$matchKey}']);\n\n"
            . "        return \$parent->{$relationship}()->updateOrCreate(\n"
            . "            ['{$matchKey}' => \$id],\n"
            . "            \$data,\n"
            . "        );\n"
            . "    }\n";

        $contents = preg_replace('/\n}\s*$/', $method . "\n}\n", $contents, 1) ?? $contents;
        $files->put($path, $contents);
        $this->line("Patched: {$path} ({$methodName})");
    }

    private function patchController(
        Filesystem $files,
        string $path,
        string $module,
        string $requestClass,
        string $methodName
    ): void {
        $contents = $files->get($path);
        $requestFqn = $this->moduleNamespace($module, 'Http\\Requests') . '\\' . $requestClass;

        foreach (['Illuminate\\Http\\JsonResponse', $requestFqn] as $import) {
            if (! str_contains($contents, "use {$import};")) {
                $contents = preg_replace(
                    '/^namespace\s+[^;]+;\n\n/m',
                    "$0use {$import};\n",
                    $contents,
                    1
                ) ?? $contents;
            }
        }

        if (str_contains($contents, "function {$methodName}(")) {
            $this->line("Controller đã có {$methodName}(): {$path}");

            return;
        }

        $method = "\n    public function {$methodName}(\n"
            . "        int \$id,\n"
            . "        {$requestClass} \$request,\n"
            . "    ): JsonResponse {\n"
            . "        \$result = \$this->action->{$methodName}(\$id, \$request->validated());\n\n"
            . "        return \$this->success(\$result, 'Saved successfully');\n"
            . "    }\n";

        $contents = preg_replace('/\n}\s*$/', $method . "\n}\n", $contents, 1) ?? $contents;
        $files->put($path, $contents);
        $this->line("Patched: {$path} ({$methodName})");
    }

    private function patchRoutes(
        Filesystem $files,
        string $apiRoutesPath,
        string $module,
        string $name,
        string $controllerClass,
        string $methodName,
        string $routeSegment,
        string $routePrefix
    ): void {
        if (! $files->exists($apiRoutesPath)) {
            return;
        }

        $contents = $files->get($apiRoutesPath);
        $controllerImport = "Modules\\{$module}\\Http\\Controllers\\{$controllerClass}";

        if (! str_contains($contents, "use {$controllerImport};")) {
            $contents = preg_replace(
                '/^<\?php\n\n/m',
                "<?php\n\nuse {$controllerImport};\n\n",
                $contents,
                1
            ) ?? $contents;
        }

        if (str_contains($contents, "[{$controllerClass}::class, '{$methodName}']")) {
            return;
        }

        $routeLine = "            Route::put('{id}/{$routeSegment}', [{$controllerClass}::class, '{$methodName}']);\n";
        $prefix = $this->findExistingRoutePrefix($contents, $name) ?? $routePrefix;

        if ($this->insertRouteIntoPrefixGroup($files, $apiRoutesPath, $contents, $prefix, $routeLine)) {
            $this->line("Patched: {$apiRoutesPath} ({$prefix}/{id}/{$routeSegment})");

            return;
        }

        $block = "\n        Route::prefix('{$routePrefix}')->group(function () {\n{$routeLine}        });\n";
        $contents2 = preg_replace(
            "/(require\s+__DIR__\s*\.\s*['\"]\\/auth\\.php['\"];\s*\n)/",
            "$1{$block}",
            $contents,
            1
        ) ?? $contents;

        if ($contents2 !== $contents) {
            $files->put($apiRoutesPath, $contents2);
            $this->line("Patched: {$apiRoutesPath} (added {$routePrefix} group)");
        }
    }

    private function findExistingRoutePrefix(string $contents, string $controllerName): ?string
    {
        $candidates = [
            Str::kebab(Str::plural($controllerName)),
            Str::kebab($controllerName),
        ];

        foreach ($candidates as $prefix) {
            if (str_contains($contents, "prefix('{$prefix}')")) {
                return $prefix;
            }
        }

        return null;
    }

    private function insertRouteIntoPrefixGroup(
        Filesystem $files,
        string $apiRoutesPath,
        string $contents,
        string $prefix,
        string $routeLine
    ): bool {
        $contents2 = preg_replace(
            "/(prefix\('{$prefix}'\)->group\(function \(\) \{\n)/",
            "$1{$routeLine}",
            $contents,
            1
        ) ?? $contents;

        if ($contents2 !== $contents) {
            $files->put($apiRoutesPath, $contents2);

            return true;
        }

        return false;
    }
}
