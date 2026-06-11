<?php

namespace App\Http\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

abstract class BaseAction
{
    public Model $model;

    /**
     * @return array<string, mixed>
     */
    protected function buildControlPayload(array $data): array
    {
        return $data;
    }

    public function create(array $data): mixed
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $attributes): mixed
    {

        return $this->transaction(function () use ($id, $attributes) {
                $model = $this->model::query()->findOrFail($id);
                $model->update($this->buildControlPayload($attributes));

                return $model->refresh();
            });
    }

    public function delete(int|string $id): mixed
    {
        return $this->transaction(function () use ($id) {
            $model = $this->model::query()->findOrFail((int) $id);
            $model->delete();

            return null;
        });
    }

    public function bulkDelete(array $ids): bool
    {
        return $this->transaction(function () use ($ids) {
            return $this->model::query()->whereIn('id', $ids)->delete() > 0;
        });
    }

    /* -----------------------------------------------------------------
     | Transaction
     |-----------------------------------------------------------------*/

    protected function transaction(callable $callback)
    {
        return DB::transaction(fn () => $callback());
    }

    /* -----------------------------------------------------------------
     | CRUD helpers (OPTIONAL)
     |-----------------------------------------------------------------*/

    /**
     * Create a new model instance with given attributes and relations.
      *
      * @param array<string, mixed> $attributes
      * @param array<string, mixed> $relations
      * @return Model
      * @throws \Throwable
     */
    protected function createModel(
        array $attributes,
        array $relations = []
    ): Model {
        /** @var Model $model */
        $model = $this->model::create($attributes);

        $this->syncRelations($model, $relations);

        return $model->refresh();
    }

    protected function updateModel(
        Model $model,
        array $attributes,
        array $relations = []
    ): Model {
        $model->update($attributes);

        $this->syncRelations($model, $relations);

        return $model->refresh();
    }

    protected function deleteModel(Model $model): void
    {
        $model->delete();
    }

    /* -----------------------------------------------------------------
     | Relationship helpers
     |-----------------------------------------------------------------*/

    protected function syncRelations(Model $model, array $relations): void
    {
        foreach ($relations as $relation => $value) {
            if (!method_exists($model, $relation)) {
                continue;
            }

            $relationObj = $model->{$relation}();

            // belongsToMany
            if (method_exists($relationObj, 'sync')) {
                $relationObj->sync($value);
            }
            // hasMany
            elseif (method_exists($relationObj, 'createMany')) {
                $relationObj->delete();
                $relationObj->createMany($value);
            }
            // hasOne
            elseif (method_exists($relationObj, 'update')) {
                $relationObj->update($value);
            }
        }
    }
}
