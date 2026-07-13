<?php

namespace Tests\Unit\Concerns;

use Illuminate\Foundation\Http\FormRequest;

trait AssertsRequestRuleKeys
{
    /**
     * @param  array<int, string>  $expectedKeys
     */
    protected function assertRequestRulesContainKeys(FormRequest $request, array $expectedKeys): void
    {
        $rules = $request->rules();
        $this->assertIsArray($rules);

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey(
                $key,
                $rules,
                sprintf('Expected rules() to contain key [%s]', $key),
            );
        }
    }
}
