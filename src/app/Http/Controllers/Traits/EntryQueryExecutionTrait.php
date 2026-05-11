<?php

namespace App\Http\Controllers\Traits;

use App\Http\Actions\BaseAction;
use App\Http\Queries\BaseQuery;
use Illuminate\Http\Request;

trait EntryQueryExecutionTrait
{
    /**
     * Execute an action with request data and return response.
     *
     * @param BaseAction $action
     * @param Request $request
     * @param string $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function executeAction(
        BaseAction $action,
        Request $request,
        string $message = 'Success',
        int $code = 200
    ) {
        $result = $action->execute($request->validated());

        return $this->success($result, $message, $code);
    }

    /**
     * Execute a query and return paginated response.
     *
     * @param BaseQuery $query
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function executeQuery(
        BaseQuery $query,
        string $message = 'Success'
    ) {
        $data = $query->paginate();

        return $this->success($data, $message);
    }

    /**
     * Execute a query and return collection response.
     *
     * @param BaseQuery $query
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function executeQueryAll(
        BaseQuery $query,
        string $message = 'Success'
    ) {
        $data = $query->all();

        return $this->success($data, $message);
    }

    /**
     * Execute a query and return single item response.
     *
     * @param BaseQuery $query
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function executeQuerySingle(
        BaseQuery $query,
        string $message = 'Success'
    ) {
        $data = $query->first();

        if (! $data) {
            return $this->error('Resource not found', 404);
        }

        return $this->success($data, $message);
    }

    /**
     * Execute a query with select items.
     *
     * @param BaseQuery $query
     * @param array $columns
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function executeQuerySelect(
        BaseQuery $query,
        array $columns = ['id', 'name'],
        string $message = 'Success'
    ) {
        $data = $query->selectItems($columns);

        return $this->success($data, $message);
    }
}
