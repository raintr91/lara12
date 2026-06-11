<?php

namespace App\Http\Controllers\Traits;

use App\Http\Actions\BaseAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

trait EntryCreateTrait
{
    /**
     * Create a new resource.
     *
     * @param Request $request
     * @param string $operation
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(
        Request $request,
        string $message = 'Created successfully',
        int $statusCode = 201
    )
    {
        try {
            $data = $request instanceof FormRequest
                ? $request->validated()
                : $request->all();
            $result = $this->action->execute($data);

            return $this->success($result, $message, $statusCode);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
