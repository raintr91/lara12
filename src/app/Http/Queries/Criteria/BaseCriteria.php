<?php

namespace App\Http\Queries\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class BaseCriteria implements Criteria
{
    /** @var array<string, array{column: string, operator: string}|callable> */
    protected array $allowedFilters = [];

    /** @var list<string> */
    protected array $allowedSorts = [];

    /** @var list<string> */
    protected array $allowedIncludes = [];

    public function __construct(
        array $allowedFilters = [],
        array $allowedSorts = [],
        array $allowedIncludes = [],
    ) {
        $this->allowedFilters = $this->normalizeAllowedFilters($allowedFilters);
        $this->allowedSorts = $allowedSorts;
        $this->allowedIncludes = $allowedIncludes;
    }

    public function apply(Builder $query, Request $request): Builder
    {
        $this->applyFilters($query, $request);
        $this->applySort($query, $request);
        $this->applyIncludes($query, $request);

        return $query;
    }

    protected function applyFilters(Builder $query, Request $request): void
    {
        if ($this->allowedFilters === []) {
            return;
        }

        $filters = $this->normalizeFilterInput($request->input('filter', []));
        if ($filters === []) {
            return;
        }

        foreach ($filters as $field => $value) {
            if (! isset($this->allowedFilters[$field])) {
                continue;
            }

            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            $handler = $this->allowedFilters[$field];

            if (is_callable($handler)) {
                $handler($query, $value);

                continue;
            }

            $column = $handler['column'] ?? $field;
            $operator = $handler['operator'] ?? 'like';

            if (strtolower((string) $operator) === 'like') {
                $query->where($column, 'like', "%{$value}%");
            } elseif (strtolower((string) $operator) === 'in' && is_array($value)) {
                $query->whereIn($column, $value);
            } else {
                $query->where($column, $operator, $value);
            }
        }
    }

    protected function applySort(Builder $query, Request $request): void
    {
        $sort = $request->get('sort');

        if (! $sort && $request->filled('order_by')) {
            $direction = strtolower((string) $request->get('sorted_by', 'asc')) === 'desc' ? '-' : '';
            $sort = $direction.$request->get('order_by');
        }

        if (! $sort) {
            return;
        }

        $direction = str_starts_with((string) $sort, '-') ? 'desc' : 'asc';
        $field = ltrim((string) $sort, '-');

        if ($this->allowedSorts !== [] && ! in_array($field, $this->allowedSorts, true)) {
            return;
        }

        $query->orderBy($field, $direction);
    }

    protected function applyIncludes(Builder $query, Request $request): void
    {
        if ($this->allowedIncludes === []) {
            return;
        }

        $includes = array_intersect(
            explode(',', (string) $request->get('include')),
            $this->allowedIncludes
        );

        if ($includes !== []) {
            $query->with($includes);
        }
    }

    /**
     * @return array<string, array{column: string, operator: string}|callable>
     */
    protected function normalizeAllowedFilters(array $allowed): array
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
                    $operator = $handler[1] ?? 'like';
                    $column = $handler[2] ?? $tupleField;

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

    /**
     * @return array<string, mixed>
     */
    protected function normalizeFilterInput(mixed $filters): array
    {
        if (is_string($filters)) {
            $decoded = json_decode($filters, true);
            $filters = is_array($decoded) ? $decoded : [];
        }

        return is_array($filters) ? $filters : [];
    }

    protected function input(Request $request, string $key, mixed $default = null): mixed
    {
        return $request->input($key, $default);
    }

    protected function text(Request $request, string $key): string
    {
        return trim((string) $request->input($key, ''));
    }

    protected function hasValue(mixed $value): bool
    {
        return $value !== null && $value !== '' && $value !== [];
    }

    protected function hasSort(Request $request): bool
    {
        return $request->filled('sort') || $request->filled('order_by');
    }
}
