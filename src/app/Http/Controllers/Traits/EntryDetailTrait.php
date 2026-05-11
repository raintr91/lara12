<?php

namespace App\Http\Controllers\Traits;

use App\Http\Queries\BaseQuery;

trait EntryDetailTrait
{
    /**
     * Get detail of a resource.
     *
     * @param BaseQuery $query
     * @param int|string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDetail(BaseQuery $query, $id)
    {
        try {
            $data = $query->findById($id);

            if (! $data) {
                return $this->error('Resource not found', 404);
            }

            return $this->success($data, 'Retrieved successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
