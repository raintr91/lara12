<?php

namespace App\Http\Controllers\Traits;

use App\Http\Requests\SelectItemRequest;
use App\Http\Resources\SelectItemResource;

trait SelectItemControllerTrait
{
    /**
     * Return the query class used for this select endpoint.
     *
     * @return class-string
     */
    abstract protected function selectItemQueryClass(): string;

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
        $queryClass = $this->selectItemQueryClass();
        $resourceClass = $this->selectItemResourceClass();

        $query = app()->makeWith($queryClass, ['request' => $request]);

        if (!method_exists($query, 'getListSelectItems')) {
            throw new \RuntimeException($queryClass . ': missing method getListSelectItems() (did you forget to use SelectItemQueryTrait?)');
        }

        $items = $query->getListSelectItems();
        $data = $resourceClass::collection($items)->resolve($request);

        return $this->success($data, 'Retrieved successfully');
    }
}
