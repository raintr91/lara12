<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response('welcome to base project', 200)->header('Content-Type', 'text/plain; charset=UTF-8');
});
