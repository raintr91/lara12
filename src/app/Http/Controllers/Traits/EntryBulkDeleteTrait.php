<?php

namespace App\Http\Controllers\Traits;

use App\Http\Actions\BaseAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

trait EntryBulkDeleteTrait
{
    /**
     * Bulk delete resources.
     *
     * @param BaseAction $action
     * @param FormRequest|Request $request
     * @param string $operation
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkDelete(BaseAction $action, $request, string $operation = 'bulk_delete')
    {
        try {
            $payload = $request instanceof FormRequest
                ? $request->validated()
                : $request->all();

            $payload['operation'] = $operation;

            $result = $action->execute($payload);

            return $this->success($result, 'Deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}