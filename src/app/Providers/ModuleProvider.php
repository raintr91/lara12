<?php

namespace App\Providers;

class ModuleProvider
{
    public static function boot(): void
    {
        app()->register(\Modules\Chain\Providers\ChainServiceProvider::class);
        //:end-boot
        //: Replace
    }

    public static function register(): void
    {
        app()->register(\Modules\Chain\Providers\ChainServiceProvider::class);
        //:end-register
        //: Replace
    }
}
