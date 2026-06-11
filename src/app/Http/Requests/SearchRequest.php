<?php

namespace App\Http\Requests;

abstract class SearchRequest extends BaseRequest
{
    protected int $maxPerPage = 100;

    public function getMaxPerPage(): int
    {
        return $this->maxPerPage;
    }

    public function rules(): array
    {
        $paginationRules = $this->wantsAllResults() ? [] : $this->paginationRules();

        return array_merge(
            $this->allResultsRules(),
            $paginationRules,
            $this->sortRules(),
            $this->filterRules(),
            $this->searchRules(),
        );
    }

    /**
     * When true, list endpoints return all matching rows (no page/per_page).
     */
    public function wantsAllResults(): bool
    {
        return filter_var($this->input('all', false), FILTER_VALIDATE_BOOLEAN);
    }

    public function shouldPaginate(): bool
    {
        return ! $this->wantsAllResults();
    }

    protected function allResultsRules(): array
    {
        return [
            'all' => ['sometimes', 'boolean'],
        ];
    }

    protected function paginationRules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.$this->maxPerPage],
        ];
    }

    protected function sortRules(): array
    {
        return [
            'sort' => ['nullable', 'string'],
            'order_by' => ['nullable', 'string'],
            'sorted_by' => ['nullable', 'string', 'in:asc,desc,ASC,DESC'],
            'direction' => ['nullable', 'string', 'in:asc,desc'],
            'include' => ['nullable', 'string'],
        ];
    }

    protected function filterRules(): array
    {
        return [
            'filter' => ['nullable', 'array'],
        ];
    }

    protected function searchRules(): array
    {
        return [];
    }

    /**
     * Fields merged into filter[] before validation.
     *
     * Examples:
     *  ['name', 'status']
     *  ['name' => 'ota_name']  // filter field => request param
     */
    protected function searchable(): array
    {
        return [];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $filter = $this->input('filter', []);
        if (is_string($filter)) {
            $decoded = json_decode($filter, true);
            $filter = is_array($decoded) ? $decoded : [];
        } elseif (! is_array($filter)) {
            $filter = [];
        }

        foreach ($this->searchable() as $field => $sourceKey) {
            if (is_int($field)) {
                $field = $sourceKey;
                $sourceKey = $field;
            }

            if (array_key_exists($field, $filter)) {
                continue;
            }

            $value = $this->input($sourceKey);
            if ($value !== null && $value !== '') {
                $filter[$field] = $value;
            }
        }

        if ($filter !== []) {
            $this->merge(['filter' => $filter]);
        }
    }
}
