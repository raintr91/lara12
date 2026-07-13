<?php

namespace Modules\Chain\Tests\Support;

use \Modules\Chain\Http\Requests\ChainRequest;
use \Modules\Chain\Http\Controllers\ChainController;
use Tests\Unit\Concerns\ExercisesRequestValidationHooks;
use Tests\Unit\Concerns\InvokesControllerMethods;

/**
 * Thin module wiring for shared unit-test concerns (api:unit-gen).
 */
trait ModuleTestSupport
{
    use ExercisesRequestValidationHooks;
    use InvokesControllerMethods;

    protected function moduleBaseRequestClass(): ?string
    {
        return \Modules\Chain\Http\Requests\ChainRequest::class;
    }

    /**
     * @return array<int, class-string>
     */
    protected function moduleControllerSkipClasses(): array
    {
        return [
            \Modules\Chain\Http\Controllers\ChainController::class,
        ];
    }
}
