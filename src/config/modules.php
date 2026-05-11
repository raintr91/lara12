<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Module Namespace
    |--------------------------------------------------------------------------
    |
    | Default module namespace.
    |
    */
    'namespace' => 'Modules',

    /*
    |--------------------------------------------------------------------------
    | Module Stubs
    |--------------------------------------------------------------------------
    |
    | Default module stubs.
    |
    */
    'stubs' => [
        'enabled' => true,
        'path' => base_path('stubs/modules'),
        'files' => [
            // Core module skeleton
            'routes/web' => 'Routes/web.php',
            'routes/api' => 'Routes/api.php',
            'scaffold/config' => 'Config/config.php',

            // Module resources
            'views/email' => 'Resources/Views/Emails/email.blade.php',
            'lang/en/messages' => 'Resources/Lang/en/messages.php',

            // Tests
            'scaffold/test/feature' => 'Tests/Feature/ExampleTest.php',
            'scaffold/test/unit' => 'Tests/Unit/ExampleTest.php',
        ],
        'replacements' => [
            // Required for generating a valid module.json (otherwise placeholders remain,
            // and follow-up generators can't find the module by name).
            'json' => ['LOWER_NAME', 'STUDLY_NAME', 'KEBAB_NAME', 'MODULE_NAMESPACE', 'PROVIDER_NAMESPACE'],

            'routes/web' => ['LOWER_NAME', 'STUDLY_NAME', 'PLURAL_LOWER_NAME', 'KEBAB_NAME', 'MODULE_NAMESPACE', 'CONTROLLER_NAMESPACE'],
            'routes/api' => ['LOWER_NAME', 'STUDLY_NAME', 'PLURAL_LOWER_NAME', 'KEBAB_NAME', 'MODULE_NAMESPACE', 'CONTROLLER_NAMESPACE'],
            'scaffold/config' => ['STUDLY_NAME'],

            'scaffold/controller' => ['LOWER_NAME', 'STUDLY_NAME', 'MODULE_NAMESPACE', 'CONTROLLER_NAMESPACE'],
            'scaffold/middleware' => ['LOWER_NAME', 'STUDLY_NAME', 'MODULE_NAMESPACE'],
            'scaffold/request' => ['LOWER_NAME', 'STUDLY_NAME', 'MODULE_NAMESPACE'],
            'resources/resource' => ['LOWER_NAME', 'STUDLY_NAME', 'MODULE_NAMESPACE'],
            'actions/create' => ['LOWER_NAME', 'STUDLY_NAME', 'MODULE_NAMESPACE'],
            'queries/list' => ['LOWER_NAME', 'STUDLY_NAME', 'MODULE_NAMESPACE'],

            'jobs/job' => ['LOWER_NAME', 'STUDLY_NAME', 'MODULE_NAMESPACE'],
            'console/command' => ['LOWER_NAME', 'STUDLY_NAME', 'MODULE_NAMESPACE'],

            'views/email' => ['LOWER_NAME', 'STUDLY_NAME'],
            'lang/en/messages' => ['LOWER_NAME', 'STUDLY_NAME'],

            'scaffold/test/feature' => ['LOWER_NAME', 'STUDLY_NAME', 'MODULE_NAMESPACE'],
            'scaffold/test/unit' => ['LOWER_NAME', 'STUDLY_NAME', 'MODULE_NAMESPACE'],

            // Optional, but keeps compatibility if you add a composer stub later.
            'composer' => ['LOWER_NAME', 'STUDLY_NAME', 'VENDOR', 'AUTHOR_NAME', 'AUTHOR_EMAIL', 'MODULE_NAMESPACE', 'PROVIDER_NAMESPACE', 'APP_FOLDER_NAME'],
        ],
        'gitkeep' => true,
        // Note: laravel-modules v12 uses the *keys* in this array to look up
        // `modules.paths.generator.<key>` and ignores the values. To avoid
        // creating duplicate lowercase folders (routes/, config/, tests/, ...),
        // we rely on `stubs.files` to create the desired structure.
        'directories' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Paths
    |--------------------------------------------------------------------------
    |
    | Override nwidart's default generator paths so module:make produces a
    | module structure that mirrors this app's layout (without an extra app/
    | folder inside each module).
    |
    */
    'paths' => [
        // Required: used to build module namespaces and resolve module locations.
        'modules' => base_path('Modules'),

        // Optional, but kept for compatibility with package defaults.
        'assets' => public_path('modules'),
        'migration' => base_path('database/migrations'),

        // Important: avoid generating into Modules/<Name>/app/*
        'app_folder' => '',

        'generator' => [
            // Keep providers, but generate them under Modules/<Name>/Providers (no app/)
            'provider' => ['path' => 'Providers', 'generate' => true],
            'event-provider' => ['path' => 'Providers', 'generate' => true],
            'route-provider' => ['path' => 'Providers', 'generate' => true],

            // Do NOT generate example controller (stubs already create base files)
            'controller' => ['path' => 'Http/Controllers', 'generate' => false],
            'request' => ['path' => 'Http/Requests', 'generate' => false],
            'filter' => ['path' => 'Http/Middleware', 'generate' => false],

            // Used by m:module when calling module:make-job / module:make-command
            'jobs' => ['path' => 'Jobs', 'generate' => false],
            'command' => ['path' => 'Console/Commands', 'generate' => false],
            'resource' => ['path' => 'Http/Resources', 'generate' => false],

            // Do NOT generate database-related folders/files in modules
            'seeder' => ['path' => 'Database/Seeders', 'generate' => false],
            'migration' => ['path' => 'Database/Migrations', 'generate' => false],
            'factory' => ['path' => 'Database/Factories', 'generate' => false],

            // Prevent lowercase duplicates if you later run other module:* generators
            'config' => ['path' => 'Config', 'generate' => false],
            'routes' => ['path' => 'Routes', 'generate' => false],
            'views' => ['path' => 'Resources/Views', 'generate' => false],
            'lang' => ['path' => 'Resources/Lang', 'generate' => false],
            'test-feature' => ['path' => 'Tests/Feature', 'generate' => false],
            'test-unit' => ['path' => 'Tests/Unit', 'generate' => false],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Scan Path
    |--------------------------------------------------------------------------
    |
    | Here you define which folder will be scanned. By default only the modules
    | folder is scanned. You can add paths if a module installs a sub-module.
    |
    */
    'scan' => [
        'enabled' => false,
        'paths' => [
            base_path('Modules/*'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Compositors
    |--------------------------------------------------------------------------
    |
    | Here you may use composers to extend or override the default manifest
    | of module information. For example, you could override the files array
    | to add custom stubs when creating a module.
    |
    */
    'compositors' => [],

    /*
    |--------------------------------------------------------------------------
    | Module activation
    |--------------------------------------------------------------------------
    |
    | Here you may activate and deactivate modules.
    |
    */
    'disabled' => [],

    /*
    |--------------------------------------------------------------------------
    | Service provider manifest path
    |--------------------------------------------------------------------------
    |
    */
    'providers' => base_path('bootstrap/cache/modules.php'),

    /*
    |--------------------------------------------------------------------------
    | Middleware to check and see if modules are installed.
    |--------------------------------------------------------------------------
    |
    */
    'isActivated' => env('MODULES_ACTIVATED', true),
];
