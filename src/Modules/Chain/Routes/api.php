<?php

use Modules\Chain\Http\Controllers\HotelController;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Module API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your module.
|
*/

Route::prefix(Str::kebab('Chain'))
    ->as(strtolower('Chain').'.')
    ->group(function () {
        // Route::get('search', [\Modules\Chain\Http\Controllers\Controller::class, 'search']);
        // Route::get('detail/{id}', [\Modules\Chain\Http\Controllers\Controller::class, 'getDetail']);
        // Route::post('/', [\Modules\Chain\Http\Controllers\Controller::class, 'create']);
        // Route::put('edit/{id}', [\Modules\Chain\Http\Controllers\Controller::class, 'update']);
        // Route::delete('delete/{id}', [\Modules\Chain\Http\Controllers\Controller::class, 'delete']);
        Route::prefix('hotel')->group(function () {
            Route::get('search', [HotelController::class, 'search']);
        });

});
