<?php

namespace App\Http\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

abstract class BaseAction
{
    /**
     * Public entry point
     * Controller chỉ gọi execute()
     */
    final public function execute(...$args)
    {
        return $this->run(...$args);
    }

    /**
     * Business logic
     * Action con tự định nghĩa param + PHPDoc
     */
    abstract protected function run(...$args);

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
     * @param class-string<Model> $modelClass
     */
    protected function create(
        string $modelClass,
        array $attributes,
        array $relations = []
    ): Model {
        /** @var Model $model */
        $model = $modelClass::create($attributes);

        $this->syncRelations($model, $relations);

        return $model->refresh();
    }

    protected function update(
        Model $model,
        array $attributes,
        array $relations = []
    ): Model {
        $model->update($attributes);

        $this->syncRelations($model, $relations);

        return $model->refresh();
    }

    protected function delete(Model $model): void
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
