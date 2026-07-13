<?php

namespace Tests\Unit\Concerns;

use Illuminate\Foundation\Http\FormRequest;
use ReflectionMethod;

/**
 * Exercise optional FormRequest validation hooks without HTTP.
 *
 * Override moduleBaseRequestClass() in a module support trait when the module
 * has a dedicated search/base request with filter field maps.
 */
trait ExercisesRequestValidationHooks
{
    protected function exerciseRequestValidationHooks(FormRequest $request): void
    {
        $request->replace([
            'page' => 1,
            'per_page' => 10,
            'filter' => ['name' => 'demo'],
            'search' => 'keyword',
        ]);

        $this->assertIsArray($request->rules());

        foreach (['defaultSort', 'searchable', 'prepareForValidation', 'withValidator'] as $method) {
            if (! method_exists($request, $method)) {
                continue;
            }

            $reflection = new ReflectionMethod($request, $method);
            if ($method === 'withValidator') {
                $validator = $this->app->make('validator')->make($request->all(), $request->rules());
                $request->withValidator($validator);
            } else {
                $reflection->setAccessible(true);
                $reflection->invoke($request);
            }
        }
    }

    /**
     * @param  class-string<FormRequest>  $requestClass
     */
    protected function makeSearchRequestInstance(string $requestClass): FormRequest
    {
        $base = $this->moduleBaseRequestClass();

        if ($base !== null && $requestClass === $base) {
            return $this->makeConcreteSearchRequestDouble($base);
        }

        if ($base !== null && is_subclass_of($requestClass, $base)) {
            return new $requestClass;
        }

        return new $requestClass;
    }

    /**
     * @return class-string<FormRequest>|null
     */
    protected function moduleBaseRequestClass(): ?string
    {
        return null;
    }

    /**
     * @param  class-string<FormRequest>  $baseRequestClass
     */
    protected function makeConcreteSearchRequestDouble(string $baseRequestClass): FormRequest
    {
        $fqcn = '\\'.ltrim($baseRequestClass, '\\');

        return eval(sprintf(
            'return new class extends %s {
                protected function allowedFilterFields(): array
                {
                    return ["name" => ["nullable", "string"]];
                }
            };',
            $fqcn,
        ));
    }
}
