<?php

namespace App\Http\Queries\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class FilterCriteria implements Criteria
{
    protected array $allowed = [];

    public function __construct(array $allowed)
    {
        $this->allowed = $this->normalizeAllowed($allowed);
    }

    private function normalizeAllowed(array $allowed): array
    {
        $normalized = [];

        foreach ($allowed as $field => $handler) {
            if (is_int($field)) {
                if (is_string($handler) && $handler !== '') {
                    $normalized[$handler] = ['column' => $handler, 'operator' => 'like'];
                    continue;
                }

                if (is_array($handler)) {
                    $tupleField = $handler[0] ?? null;
                    $operator   = $handler[1] ?? 'like';
                    $column     = $handler[2] ?? $tupleField;

                    if (is_string($tupleField) && $tupleField !== '') {
                        $normalized[$tupleField] = [
                            'column' => (is_string($column) && $column !== '') ? $column : $tupleField,
                            'operator' => (is_string($operator) && $operator !== '') ? $operator : 'like',
                        ];
                    }
                }

                continue;
            }

            if ($handler === null || $handler === true) {
                $normalized[$field] = ['column' => $field, 'operator' => 'like'];
                continue;
            }

            if (is_callable($handler)) {
                $normalized[$field] = $handler;
                continue;
            }

            if (is_string($handler) && $handler !== '') {
                $normalized[$field] = ['column' => $handler, 'operator' => 'like'];
                continue;
            }

            if (is_array($handler)) {
                $column = $handler['column'] ?? $handler[0] ?? $field;
                $operator = $handler['operator'] ?? $handler['op'] ?? $handler[1] ?? 'like';

                $normalized[$field] = [
                    'column' => (is_string($column) && $column !== '') ? $column : $field,
                    'operator' => (is_string($operator) && $operator !== '') ? $operator : 'like',
                ];
            }
        }

        return $normalized;
    }

    public function apply(Builder $query, Request $request): Builder
    {
        $filters = $request->input('filter', []);
        if (!is_array($filters)) {
            $filters = [];
        }

        foreach ($filters as $field => $value) {
            if (!isset($this->allowed[$field])) {
                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            $handler = $this->allowed[$field];

            if (is_callable($handler)) {
                $handler($query, $value);
            } else {
                $column = $handler['column'] ?? $field;
                $operator = $handler['operator'] ?? 'like';

                if (strtolower((string) $operator) === 'like') {
                    $query->where($column, 'like', "%$value%");
                } elseif (strtolower((string) $operator) === 'in' && is_array($value)) {
                    $query->whereIn($column, $value);
                } else {
                    $query->where($column, $operator, $value);
                }
            }
        }

        return $query;
    }
}
