<?php

namespace App\Http\Queries;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Queries\Criteria\Criteria;
use App\Http\Queries\Criteria\FilterCriteria;
use App\Http\Queries\Criteria\IncludeCriteria;
use App\Http\Queries\Criteria\SortCriteria;

abstract class BaseQuery
{
    protected Builder $query;
    protected Request $request;

    /** @var Criteria[] */
    protected array $criteria = [];

    /** @var string|null */
    protected ?string $resource = null;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->query   = $this->newQuery();
        $this->boot();
    }

    /**
     * BẮT BUỘC: return Model::query()
     */
    abstract protected function newQuery(): Builder;

    /**
     * Override nếu cần add criteria mặc định
     */
    protected function boot(): void
    {
        $filters = $this->filters();
        if (!empty($filters)) {
            $this->pushCriteria(new FilterCriteria($filters));
        }

        $sorts = $this->sorts();
        if (!empty($sorts)) {
            $this->pushCriteria(new SortCriteria($sorts));
        }

        $includes = $this->includes();
        if (!empty($includes)) {
            $this->pushCriteria(new IncludeCriteria($includes));
        }
    }

    /**
     * L5-like: child query declares allowed filters.
     * Example:
     *  return [
     *    ['name', 'like'],
     *    ['status', '='],
     *  ];
     */
    protected function filters(): array
    {
        return [];
    }

    /**
     * Allowed sort fields.
     */
    protected function sorts(): array
    {
        return [];
    }

    /**
     * Allowed include relations.
     */
    protected function includes(): array
    {
        return [];
    }

    /* -------------------------------------------------
     | Criteria handling
     | -------------------------------------------------
     */

    public function pushCriteria(Criteria $criteria): static
    {
        $this->criteria[] = $criteria;
        return $this;
    }

    protected function applyCriteria(): Builder
    {
        return collect($this->criteria)->reduce(
            fn (Builder $q, Criteria $c) => $c->apply($q, $this->request),
            $this->query
        );
    }

    /* -------------------------------------------------
     | Query results
     | -------------------------------------------------
     */

    public function get()
    {
        $results = $this->applyCriteria()->get();

        if (!$this->resource) {
            return $results;
        }

        return $this->resource::collection($results);
    }

    public function first()
    {
        $model = $this->applyCriteria()->first();

        if (!$model || !$this->resource) {
            return $model;
        }

        return new $this->resource($model);
    }

    /**
     * Find a model by primary key (after applying criteria).
     *
     * @param int|string $id
     */
    public function findById($id)
    {
        $model = $this->applyCriteria()->find($id);

        if (! $model || ! $this->resource) {
            return $model;
        }

        return new $this->resource($model);
    }

    /**
     * Get all results without pagination.
     */
    public function all()
    {
        return $this->get();
    }

    /**
     * Select specific columns and return collection.
     *
     * @param array $columns
     * @return \Illuminate\Support\Collection
     */
    public function selectItems(array $columns = ['id', 'name'])
    {
        return $this->applyCriteria()->select($columns)->get();
    }

    /**
     * Pluck a single column.
     *
     * @param string $column
     * @param string|null $key
     * @return \Illuminate\Support\Collection
     */
    public function pluck(string $column, ?string $key = null)
    {
        return $this->applyCriteria()->pluck($column, $key);
    }

    /**
     * Count total results.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->applyCriteria()->count();
    }

    /**
     * Check if results exist.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return $this->applyCriteria()->exists();
    }

    public function paginate(): LengthAwarePaginator
    {
        $paginator = $this->applyCriteria()->paginate(
            $this->perPage(),
            ['*'],
            'page',
            $this->page()
        );

        if (!$this->resource) {
            return $paginator;
        }

        return $paginator->through(fn ($item) => new $this->resource($item));
    }

    /* -------------------------------------------------
     | Resource transformation
     | -------------------------------------------------
     */

    /**
     * Set the resource class for transformation.
     *
     * @param string|JsonResource $resource
     * @return $this
     */
    public function setResource($resource): static
    {
        $this->resource = $resource;
        return $this;
    }

    /**
     * Transform collection of models using the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function toResource()
    {
        if (!$this->resource) {
            return $this->get();
        }
        return $this->resource::collection($this->get());
    }

    /**
     * Transform single model using the resource.
     *
     * @return JsonResource|Model|null
     */
    public function toResourceSingle()
    {
        $model = $this->first();

        if (!$model || !$this->resource) {
            return $model;
        }

        return new $this->resource($model);
    }

    /**
     * Transform paginated results using the resource.
     *
     * @return LengthAwarePaginator
     */
    public function toResourcePaginated()
    {
        $paginator = $this->paginate();

        if (!$this->resource) {
            return $paginator;
        }

        return $paginator->through(fn ($item) => new $this->resource($item));
    }

    /* -------------------------------------------------
     | Pagination helpers (CUSTOM)
     | -------------------------------------------------
     */

    protected function perPage(): int
    {
        return min(
            max((int) $this->request->get('per_page', 15), 1),
            100
        );
    }

    protected function page(): int
    {
        return max((int) $this->request->get('page', 1), 1);
    }
}
