<?php

namespace Modules\Chain\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'Chain';

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $this->mapApiRoutes();

        if (is_file(module_path($this->name, '/Routes/web.php'))) {
            $this->mapWebRoutes();
        }
    }

    protected function mapWebRoutes(): void
    {
        Route::middleware('web')->group(module_path($this->name, '/Routes/web.php'));
    }

    protected function mapApiRoutes(): void
    {
        Route::middleware('api')
            ->prefix('api/chain')
            ->name('api.chain.')
            ->group(module_path($this->name, '/Routes/api.php'));
    }
}
