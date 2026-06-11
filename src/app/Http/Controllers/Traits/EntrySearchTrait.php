<?php

namespace App\Http\Controllers\Traits;

use App\Http\Queries\BaseQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

trait EntrySearchTrait
{
    /**
     * Paginated list/search via injected query (preferred).
     */
    protected function listFromQuery(
        BaseQuery $query,
        string $message = 'Retrieved successfully',
        ?array $meta = null
    ): JsonResponse {
        try {
            return $this->paginatedQueryResponse($query->paginate(), $message, $meta);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * List/search: pass {@see BaseQuery} or set {@see $query} on the controller (legacy).
     */
    public function search(
        ?BaseQuery $query = null,
        string $message = 'Retrieved successfully',
        ?array $meta = null
    ): JsonResponse {
        $resolved = $query ?? (property_exists($this, 'query') ? $this->query : null);

        if (! $resolved instanceof BaseQuery) {
            return $this->error('Query is not configured for search.', 500);
        }

        return $this->listFromQuery($resolved, $message, $meta);
    }

    protected function paginatedQueryResponse(
        mixed $data,
        string $message = 'Retrieved successfully',
        ?array $meta = null
    ): JsonResponse {
        if ($data instanceof LengthAwarePaginator) {
            return $this->success(
                $data->items(),
                $message,
                200,
                array_merge([
                    'pagination' => [
                        'current_page' => $data->currentPage(),
                        'per_page' => $data->perPage(),
                        'last_page' => $data->lastPage(),
                        'total' => $data->total(),
                    ],
                ], $meta ?? [])
            );
        }

        return $this->success($data, $message, 200, $meta);
    }
}
