<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\AsksToOverwriteExisting;
use App\Console\Commands\Concerns\GeneratesModuleFiles;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeModuleAuthApiCommand extends BaseCommand
{
    use GeneratesModuleFiles;
    use AsksToOverwriteExisting;

    protected $signature = 'm:auth-api {module : Module name (StudlyCase)} {model : App model base name (e.g. User or UserModel)}
                            {--force : Overwrite if files exist}
                            {--auth-middleware=auth:sanctum : Middleware for protected auth routes (e.g. auth:sanctum or auth)}
                            {--auth-controller= : yes|no}
                            {--auth-routes= : yes|no}
                            {--register-request= : yes|no}
                            {--login-request= : yes|no}
                            {--forgot-request= : yes|no}
                            {--reset-request= : yes|no}
                            {--change-password-request= : yes|no}
                            {--authenticatable-model= : yes|no}
                            {--skip-questions : Do not ask questions for unspecified options; use defaults}';

    protected $description = 'Scaffold module Auth API (register/login/reset password) for a module, using a specified App\\Models\\... model.';

    public function handle()
    {
        /** @var Filesystem $files */
        $files = $this->laravel['files'];

        $module = $this->studly((string) $this->argument('module'));
        if (! $this->ensureModuleExists($files, $module)) {
            $this->error("Module [$module] does not exist. Run: php artisan m:module {$module}");
            return 1;
        }

        $rawModel = Str::studly((string) $this->argument('model'));
        [$modelClass, $modelPath, $createdModel] = $this->ensureAuthenticatableModel($files, $rawModel);

        $moduleNamespace = config('modules.namespace', 'Modules');
        $moduleRequestBase = $module.'Request';
        $moduleControllerBase = $module.'Controller';

        $moduleRequestBasePath = $this->moduleRoot($module)."/Http/Requests/{$moduleRequestBase}.php";
        if (! $files->exists($moduleRequestBasePath)) {
            $this->error("Missing module base request: {$moduleRequestBasePath}. Re-create module or run your module request generator.");
            return 1;
        }

        $moduleControllerBasePath = $this->moduleRoot($module)."/Http/Controllers/{$moduleControllerBase}.php";
        if (! $files->exists($moduleControllerBasePath)) {
            $this->error("Missing module base controller: {$moduleControllerBasePath}. Re-create module or run: php artisan m:module {$module}");
            return 1;
        }

        $authControllerPath = $this->moduleRoot($module)."/Http/Controllers/Auth/AuthController.php";
        $authControllerAction = $this->askCreateOrOverwrite(
            $files,
            'auth-controller',
            "Create {$module} AuthController?",
            $authControllerPath,
            true
        );

        $authMiddleware = (string) $this->option('auth-middleware');
        if ($authMiddleware === '') {
            $authMiddleware = 'auth:sanctum';
        }

        // Routes
        $authRoutesPath = $this->moduleRoot($module).'/Routes/auth.php';
        $authRoutesAction = $this->askCreateOrOverwrite($files, 'auth-routes', 'Create auth routes file (Routes/auth.php)?', $authRoutesPath, true);
        if ($authRoutesAction === 'create' || $authRoutesAction === 'overwrite') {
            $routes = $this->renderStub($files, base_path('stubs/modules/auth-api/auth-routes.stub'), [
                'MODULE_NAMESPACE' => $moduleNamespace,
                'MODULE' => $module,
                'AUTH_MIDDLEWARE' => $authMiddleware,
            ]);

            try {
                $this->putFile($files, $authRoutesPath, $routes, $authRoutesAction === 'overwrite' || (bool) $this->option('force'));
            } catch (\RuntimeException $e) {
                $this->error($e->getMessage());
                return 1;
            }

            $this->info("Created: {$authRoutesPath}");
        }

        // Auto-load Routes/auth.php from Routes/api.php (inside the module group)
        $this->ensureAuthRoutesLoaded($files, $module);

        $requestDir = $this->moduleRoot($module)."/Http/Requests/Auth";
        $registerRequestPath = $requestDir.'/RegisterRequest.php';
        $loginRequestPath = $requestDir.'/LoginRequest.php';
        $forgotRequestPath = $requestDir.'/ForgotPasswordRequest.php';
        $resetRequestPath = $requestDir.'/ResetPasswordRequest.php';
        $changePassRequestPath = $requestDir.'/ChangePasswordRequest.php';

        $registerAction = $this->askCreateOrOverwrite($files, 'register-request', 'Create RegisterRequest?', $registerRequestPath, true);
        $loginAction = $this->askCreateOrOverwrite($files, 'login-request', 'Create LoginRequest?', $loginRequestPath, true);
        $forgotAction = $this->askCreateOrOverwrite($files, 'forgot-request', 'Create ForgotPasswordRequest?', $forgotRequestPath, true);
        $resetAction = $this->askCreateOrOverwrite($files, 'reset-request', 'Create ResetPasswordRequest?', $resetRequestPath, true);
        $changePassAction = $this->askCreateOrOverwrite($files, 'change-password-request', 'Create ChangePasswordRequest?', $changePassRequestPath, true);

        if ($authControllerAction === 'create' || $authControllerAction === 'overwrite') {
            $contents = $this->renderStub($files, base_path('stubs/modules/auth-api/auth-controller.stub'), [
                'NAMESPACE' => $this->moduleNamespace($module, 'Http\\Controllers\\Auth'),
                'MODEL_FQCN' => 'App\\Models\\'.$modelClass,
                'MODEL_CLASS' => $modelClass,
                'MODULE_NAMESPACE' => $moduleNamespace,
                'MODULE' => $module,
            ]);

            try {
                $this->putFile($files, $authControllerPath, $contents, $authControllerAction === 'overwrite' || (bool) $this->option('force'));
            } catch (\RuntimeException $e) {
                $this->error($e->getMessage());
                return 1;
            }

            $this->info("Created: {$authControllerPath}");
        }

        $reqNamespace = $this->moduleNamespace($module, 'Http\\Requests\\Auth');

        $this->maybeWriteRequestStub($files, $registerRequestPath, $registerAction, 'stubs/modules/auth-api/requests/register-request.stub', $reqNamespace, $moduleNamespace, $module);
        $this->maybeWriteRequestStub($files, $loginRequestPath, $loginAction, 'stubs/modules/auth-api/requests/login-request.stub', $reqNamespace, $moduleNamespace, $module);
        $this->maybeWriteRequestStub($files, $forgotRequestPath, $forgotAction, 'stubs/modules/auth-api/requests/forgot-password-request.stub', $reqNamespace, $moduleNamespace, $module);
        $this->maybeWriteRequestStub($files, $resetRequestPath, $resetAction, 'stubs/modules/auth-api/requests/reset-password-request.stub', $reqNamespace, $moduleNamespace, $module);
        $this->maybeWriteRequestStub($files, $changePassRequestPath, $changePassAction, 'stubs/modules/auth-api/requests/change-password-request.stub', $reqNamespace, $moduleNamespace, $module);

        $this->newLine();
        $this->info('Routes generated: Modules/'.$module.'/Routes/auth.php and auto-loaded from Modules/'.$module.'/Routes/api.php');
        $this->line('If you want to adjust middleware, re-run with: --auth-middleware=auth or --auth-middleware=auth:sanctum');

        if ($createdModel) {
            $this->newLine();
            $this->warn("Created model: {$modelPath}");
            $this->warn('If you want Password::reset() to work, ensure auth provider + password broker config point to this model.');
        }

        $this->newLine();
        $this->line('Note: API token is only returned if Sanctum is installed and the model supports createToken().');

        return 0;
    }

    private function ensureAuthRoutesLoaded(Filesystem $files, string $module): void
    {
        $apiRoutesPath = $this->moduleRoot($module).'/Routes/api.php';
        if (! $files->exists($apiRoutesPath)) {
            $this->warn("Module api routes not found: {$apiRoutesPath}");
            return;
        }

        $contents = $files->get($apiRoutesPath);
        $needle = "require __DIR__.'/auth.php';";
        if (str_contains($contents, $needle)) {
            return;
        }

        $lines = explode("\n", $contents);
        $out = [];
        $inserted = false;

        // Match common formatting variants:
        // - ->group(function () {
        // - ->group(function() {
        // - ->group(function (){
        // - ->group(function(){
        $groupOpenRegex = '/->group\s*\(\s*function\s*\(\s*\)\s*\)\s*\{\s*$/';
        $groupOpenWithSpaceRegex = '/->group\s*\(\s*function\s*\(\s*\)\s*\{\s*$/';

        foreach ($lines as $line) {
            $out[] = $line;

            if ($inserted) {
                continue;
            }

            $trimmed = rtrim($line);
            $matchesGroup = preg_match($groupOpenRegex, $trimmed) === 1
                || preg_match($groupOpenWithSpaceRegex, $trimmed) === 1
                || str_contains($trimmed, '->group(function');

            if ($matchesGroup && str_contains($trimmed, '{')) {
                $indent = preg_replace('/[^ \t].*/', '', $line);
                $out[] = $indent.'    '.$needle;
                $inserted = true;
            }
        }

        if (! $inserted) {
            $this->warn('Could not safely auto-insert auth routes include into: '.$apiRoutesPath);
            $this->warn("Please add this line inside your module route group: {$needle}");
            return;
        }

        $updated = implode("\n", $out);
        if ($updated !== $contents) {
            $files->put($apiRoutesPath, $updated);
            $this->info("Updated: {$apiRoutesPath} (auto-load auth.php)");
        }
    }

    private function maybeWriteRequestStub(
        Filesystem $files,
        string $path,
        string $action,
        string $stubRelPath,
        string $namespace,
        string $moduleNamespace,
        string $module
    ): void {
        if (! ($action === 'create' || $action === 'overwrite')) {
            return;
        }

        $contents = $this->renderStub($files, base_path($stubRelPath), [
            'NAMESPACE' => $namespace,
            'MODULE_NAMESPACE' => $moduleNamespace,
            'MODULE' => $module,
        ]);

        $this->putFile($files, $path, $contents, $action === 'overwrite' || (bool) $this->option('force'));
        $this->info("Created: {$path}");
    }

    /**
     * Ensure we have an App\\Models\\<Model> class that is Authenticatable.
     *
     * Resolution order:
     * - If input already ends with Model: try <Input>, then <Input without Model>
     * - Else: try <Input>Model first (convention), then <Input>
     * - If none exists: create <Input>Model
     */
    private function ensureAuthenticatableModel(Filesystem $files, string $rawModel): array
    {
        $inputEndsWithModel = Str::endsWith($rawModel, 'Model');

        $baseName = $inputEndsWithModel ? Str::beforeLast($rawModel, 'Model') : $rawModel;
        $preferred = $inputEndsWithModel ? $rawModel : $baseName.'Model';
        $fallback = $inputEndsWithModel ? $baseName : $baseName;

        $candidates = $inputEndsWithModel
            ? [$rawModel, $baseName]
            : [$baseName.'Model', $baseName];

        foreach ($candidates as $candidate) {
            $path = app_path("Models/{$candidate}.php");
            if ($files->exists($path)) {
                $fqcn = 'App\\Models\\'.$candidate;
                if (class_exists($fqcn) && is_subclass_of($fqcn, \Illuminate\Contracts\Auth\Authenticatable::class)) {
                    return [$candidate, $path, false];
                }

                // File exists but class isn't Authenticatable; warn and keep going.
                $this->warn("Model exists but is not Authenticatable (or autoload failed): {$fqcn}");
            }
        }

        $modelClass = $preferred;
        $modelPath = app_path("Models/{$modelClass}.php");

        $action = $this->askCreateOrOverwrite($files, 'authenticatable-model', "Create app model {$modelClass} (Authenticatable)?", $modelPath, true);
        if (! ($action === 'create' || $action === 'overwrite')) {
            $this->error('Cannot continue without an Authenticatable model.');
            return [$modelClass, $modelPath, false];
        }

        $table = Str::snake(Str::pluralStudly($baseName));

        $contents = $this->renderStub($files, base_path('stubs/app/authenticatable-model.stub'), [
            'CLASS' => $modelClass,
            'TABLE' => $table,
            'FACTORY_CLASS' => $baseName.'Factory',
        ]);

        $this->putFile($files, $modelPath, $contents, $action === 'overwrite' || (bool) $this->option('force'));

        return [$modelClass, $modelPath, true];
    }
}
