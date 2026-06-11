<?php

namespace App\Http\Controllers\Traits;

use App\Http\Actions\BaseAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

trait EntryUpdateTrait
{
    /**
     * Update an existing resource.
     *
     * @param Request $request
     * @param int|string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(
        Request $request,
        $id,
        string $message = 'Updated successfully'
    )
    {
        try {
            $data = $request instanceof FormRequest
                ? $request->validated()
                : $request->all();

            $result = $this->action->update($id, $data);

            return $this->success($result, $message);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
