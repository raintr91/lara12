<?php

namespace Tests\Unit\Concerns;

trait AssertsActionRelationshipKeys
{
    /**
     * @param  class-string  $modelFqcn
     * @param  array<int, string>  $relationNames
     */
    protected function assertModelDefinesRelationships(string $modelFqcn, array $relationNames): void
    {
        foreach ($relationNames as $relation) {
            $this->assertTrue(
                method_exists($modelFqcn, $relation),
                sprintf('Expected model [%s] to define relationship method [%s]', $modelFqcn, $relation),
            );
        }
    }
}
