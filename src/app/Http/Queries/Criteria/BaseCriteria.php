<?php

namespace App\Http\Queries\Criteria;

use Illuminate\Database\Eloquent\Builder;

abstract class BaseCriteria
{
    /**
     * Apply the criteria to the query builder.
     *
     * @param Builder $query
     * @param array $data
     * @return Builder
     */
    abstract public function apply(Builder $query, array $data): Builder;

    /**
     * Get a value from the data array with a default fallback.
     *
     * @param array $data
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getData(array $data, string $key, $default = null)
    {
        return $data[$key] ?? $default;
    }

    /**
     * Check if a key exists in the data array.
     *
     * @param array $data
     * @param string $key
     * @return bool
     */
    protected function hasData(array $data, string $key): bool
    {
        return isset($data[$key]);
    }

    /**
     * Check if a value is empty or null.
     *
     * @param mixed $value
     * @return bool
     */
    protected function isEmpty($value): bool
    {
        return is_null($value) || $value === '' || $value === [];
    }
}
