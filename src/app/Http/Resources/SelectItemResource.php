<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SelectItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $keyField = (string) $request->input('key');
        $nameFields = $request->input('name', []);
        $info = $request->input('info', []);

        $nameFields = is_array($nameFields) ? array_values(array_filter($nameFields, fn ($v) => is_string($v) && $v !== '')) : [];
        $info = is_array($info) ? array_values(array_filter($info, fn ($v) => is_string($v) && $v !== '')) : [];

        $keyValue = data_get($this->resource, $keyField);

        $name = null;
        if (count($nameFields) === 1) {
            $name = data_get($this->resource, $nameFields[0]);
        } elseif (count($nameFields) > 1) {
            $parts = [];
            foreach ($nameFields as $f) {
                $v = data_get($this->resource, $f);
                if ($v !== null && $v !== '') {
                    $parts[] = $v;
                }
            }
            $name = implode(' ', $parts);
        }

        $infoPayload = [];

        // If name has multiple fields, put the remaining fields into info as requested.
        if (count($nameFields) > 1) {
            foreach (array_slice($nameFields, 1) as $f) {
                $infoPayload[$f] = data_get($this->resource, $f);
            }
        }

        foreach ($info as $item) {
            if (str_contains($item, '.')) {
                [$relation, $field] = explode('.', $item, 2);
                if ($relation !== '' && $field !== '') {
                    $infoPayload[$relation] ??= [];
                    $infoPayload[$relation][$field] = data_get($this->resource, $item);
                }
                continue;
            }

            // Scalar field or relation name (if loaded).
            $infoPayload[$item] = $this->whenLoaded($item, fn () => $this->{$item}, data_get($this->resource, $item));
        }

        return [
            'key' => $keyValue,
            'name' => $name,
            'info' => $infoPayload,
        ];
    }
}
