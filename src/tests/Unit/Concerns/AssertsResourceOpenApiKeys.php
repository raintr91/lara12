<?php

namespace Tests\Unit\Concerns;

trait AssertsResourceOpenApiKeys
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $expectedKeys
     */
    protected function assertResourceArrayHasKeys(array $data, array $expectedKeys): void
    {
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey(
                $key,
                $data,
                sprintf('Expected resource array to contain key [%s]', $key),
            );
        }
    }
}
