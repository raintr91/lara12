<?php

namespace Modules\Chain\Http\Resources;

use Illuminate\Http\Request;

class HotelResource extends ChainResource
{
    protected function fields(Request $request): array
    {
        return [
            'name' => $this->resource->name ?? null,
        ];
    }

    protected function relations(Request $request): array
    {
        if (! $this->resource->relationLoaded('managers')) {
            return [
                'managers' => [],
            ];
        }

        return [
            'managers' => $this->managers->map(fn ($manager) => [
                'id' => $manager->id ?? null,
                'full_name' => $manager->full_name ?? null,
            ])->all(),
        ];
    }
}
