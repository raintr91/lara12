<?php

namespace App\Http\Controllers\Traits;

use App\Http\Actions\BaseAction;
use Illuminate\Http\Request;

trait EntryUpdateTrait
{
    /**
     * Update an existing resource.
     *
     * @param BaseAction $action
     * @param Request $request
     * @param int|string $id
     * @param string $operation
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(BaseAction $action, Request $request, $id, string $operation = 'update')
    {
        try {
            $data = array_merge($request->validated(), ['id' => $id, 'operation' => $operation]);
            $result = $action->execute($data);

            return $this->success($result, 'Updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
