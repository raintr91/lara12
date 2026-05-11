<?php

namespace App\Http\Controllers\Traits;

use App\Http\Actions\BaseAction;

trait EntryDeleteTrait
{
    /**
     * Delete a resource.
     *
     * @param BaseAction $action
     * @param int|string $id
     * @param string $operation
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(BaseAction $action, $id, string $operation = 'delete')
    {
        try {
            $action->execute(['id' => $id, 'operation' => $operation]);

            return $this->success(null, 'Deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
