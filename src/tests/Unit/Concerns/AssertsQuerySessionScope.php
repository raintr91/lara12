<?php

namespace Tests\Unit\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait AssertsQuerySessionScope
{
    protected function assertQueryBuilderAppliesScope(Builder $builder, string $scopeMethod): void
    {
        $this->assertTrue(
            method_exists($builder->getModel(), 'scope'.ucfirst($scopeMethod))
                || $this->builderHasWhereForScope($builder),
            sprintf('Query builder should apply scope [%s]', $scopeMethod),
        );
    }

    private function builderHasWhereForScope(Builder $builder): bool
    {
        return count($builder->getQuery()->wheres) > 0;
    }
}
