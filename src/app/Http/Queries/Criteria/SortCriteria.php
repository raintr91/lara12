<?php

namespace App\Http\Queries\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class SortCriteria implements Criteria
{
    protected array $allowed;

    public function __construct(array $allowed = [])
    {
        $this->allowed = $allowed;
    }

    public function apply(Builder $query, Request $request): Builder
    {
        $sort = $request->get('sort');

        if (! $sort && $request->filled('order_by')) {
            $direction = strtolower((string) $request->get('sorted_by', 'asc')) === 'desc' ? '-' : '';
            $sort = $direction . $request->get('order_by');
        }

        if (!$sort) {
            return $query;
        }

        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim($sort, '-');

        if (!empty($this->allowed) && !in_array($field, $this->allowed)) {
            return $query;
        }

        return $query->orderBy($field, $direction);
    }
}
