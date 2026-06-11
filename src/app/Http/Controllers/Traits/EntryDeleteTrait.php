<?php

namespace App\Http\Controllers\Traits;

use App\Http\Actions\BaseAction;

trait EntryDeleteTrait
{
    /**
     * Delete a resource.
     *
     * @param int|string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(
        $id,
        string $message = 'Deleted successfully'
    )
    {
        try {
            $this->action->delete($id);

            return $this->success(null, $message);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
