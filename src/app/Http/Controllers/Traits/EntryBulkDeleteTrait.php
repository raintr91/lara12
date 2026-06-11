<?php

namespace App\Http\Controllers\Traits;

use App\Http\Requests\BulkDeleteRequest;

trait EntryBulkDeleteTrait
{
    /**
     * Bulk delete resources.
     */
    public function bulkDelete(
        BulkDeleteRequest $request,
        string $message = 'Deleted successfully'
    ) {
        try {
            $result = $this->action->bulkDelete($request->ids());

            return $this->success($result, $message);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
