<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

abstract class BaseResource extends JsonResource
{
    /**
     * Main transform entry
     */
    public function toArray(Request $request): array
    {
        return array_filter([
            'id' => $this->getKey(),

            ...$this->fields($request),
            ...$this->relations($request),
            ...$this->timestamps(),
        ], fn ($value) => !is_null($value));
    }

    /**
     * Attributes của resource
     * BẮT BUỘC override
     */
    abstract protected function fields(Request $request): array;

    /**
     * Relations transform (override nếu cần)
     */
    protected function relations(Request $request): array
    {
        return [];
    }

    /**
     * Standard timestamps
     */
    protected function timestamps(): array
    {
        return [
            'created_at' => $this->when(
                $this->resource?->created_at,
                fn () => $this->resource->created_at?->toISOString()
            ),
            'updated_at' => $this->when(
                $this->resource?->updated_at,
                fn () => $this->resource->updated_at?->toISOString()
            ),
        ];
    }

    /* -----------------------------------------------------------------
     | Helpers – giống l5 transform nhưng clean hơn
     | -----------------------------------------------------------------
     */

    /**
     * Map hasMany / belongsToMany
     */
    protected function collectionRelation(
        string $relation,
        string $resourceClass
    ) {
        return $this->whenLoaded(
            $relation,
            fn () => $resourceClass::collection($this->$relation)
        );
    }

    /**
     * Map hasOne / belongsTo
     */
    protected function item(
        string $relation,
        string $resourceClass
    ) {
        return $this->whenLoaded(
            $relation,
            fn () => new $resourceClass($this->$relation)
        );
    }

    /**
     * Rename key helper
     */
    protected function rename(string $key, mixed $value): array
    {
        return [$key => $value];
    }

    /**
     * Snake case helper (optional)
     */
    protected function snake(string $key): string
    {
        return Str::snake($key);
    }
}
