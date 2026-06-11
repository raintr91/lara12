<?php

namespace App\Http\Controllers\Traits;

use App\Http\Requests\SelectItemRequest;
use App\Http\Resources\SelectItemResource;

trait SelectItemControllerTrait
{

    /**
     * Return the resource class used to transform select items.
     *
     * @return class-string<\Illuminate\Http\Resources\Json\JsonResource>
     */
    protected function selectItemResourceClass(): string
    {
        return SelectItemResource::class;
    }

    public function getListSelect(SelectItemRequest $request)
    {
        $resourceClass = $this->selectItemResourceClass();

        $items = $this->query->getListSelectItems($request);
        $data = $resourceClass::collection($items)->resolve($request);

        return $this->success($data, 'Retrieved successfully');
    }
}
