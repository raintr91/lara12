<?php

namespace Tests\Unit\Concerns;

use Illuminate\Foundation\Http\FormRequest;

trait AssertsSearchRequestPagination
{
    protected function assertSearchRequestMaxPerPage(FormRequest $request, int $expectedMax): void
    {
        if (! method_exists($request, 'getMaxPerPage')) {
            $this->markTestSkipped('Request does not expose getMaxPerPage().');
        }

        $this->assertSame($expectedMax, $request->getMaxPerPage());
    }
}
