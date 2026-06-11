<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Http\JsonResponse;

trait EntryStatsTrait
{
    public function stats(): JsonResponse
    {
        try {
            return $this->success($this->query->stats(), 'Retrieved successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
