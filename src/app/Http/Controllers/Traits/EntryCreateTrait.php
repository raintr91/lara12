<?php

namespace App\Http\Controllers\Traits;

use App\Http\Actions\BaseAction;
use Illuminate\Http\Request;

trait EntryCreateTrait
{
    /**
     * Create a new resource.
     *
     * @param BaseAction $action
     * @param Request $request
     * @param string $operation
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(BaseAction $action, Request $request, string $operation = 'create')
    {
        try {
            $data = array_merge($request->validated(), ['operation' => $operation]);
            $result = $action->execute($data);

            return $this->success($result, 'Created successfully', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
