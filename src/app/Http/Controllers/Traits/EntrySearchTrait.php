<?php

namespace App\Http\Controllers\Traits;

use App\Http\Queries\BaseQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * List + phân trang. Filter / sort / include do {@see BaseQuery} xử lý trong constructor
 * (FilterCriteria, SortCriteria, IncludeCriteria) từ query string, ví dụ:
 * `?filter[name]=foo&filter[status]=1&sort=-created_at`.
 */
trait EntrySearchTrait
{
    /**
     * Search/List resources with pagination.
     *
     * @param BaseQuery $query Đã inject kèm Request; subclass phải khai báo {@see BaseQuery::filters()} (và sorts/includes nếu cần).
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(BaseQuery $query)
    {
        try {
            $data = $query->paginate();

            if ($data instanceof LengthAwarePaginator) {
                return $this->success(
                    $data->items(),
                    'Retrieved successfully',
                    200,
                    [
                        'pagination' => [
                            'current_page' => $data->currentPage(),
                            'per_page' => $data->perPage(),
                            'last_page' => $data->lastPage(),
                            'total' => $data->total(),
                        ],
                    ]
                );
            }

            // BaseQuery::paginate() always returns LengthAwarePaginator; keep fallback for defensive compatibility.
            // @codeCoverageIgnoreStart
            return $this->success($data, 'Retrieved successfully');
            // @codeCoverageIgnoreEnd
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
