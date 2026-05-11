<?php

namespace App\Http\Queries\Traits;

use App\Http\Requests\SelectItemRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

trait SelectItemQueryTrait
{
    /**
     * Build a minimal select list for <select> options.
     *
     * Returns Eloquent models with only needed columns (and optionally loaded relations).
     */
    public function getListSelectItems(): Collection
    {
        $request = $this->request;

        if (!$request instanceof SelectItemRequest) {
            throw new \RuntimeException(static::class . ': request must extend ' . SelectItemRequest::class);
        }

        $model = $request->modelInstance();

        $keyField = $request->keyField();
        $nameFields = $request->nameFields();
        $info = $request->infoFields();

        [$scalarInfoFields, $relations, $relationFields] = $this->parseInfo($model, $info);

        $columns = array_values(array_filter(array_unique(array_merge(
            [$keyField],
            $nameFields,
            $scalarInfoFields
        ))));

        $builder = $this->applyCriteria();

        // Ensure required columns for relations exist on parent.
        foreach (array_unique(array_merge($relations, array_keys($relationFields))) as $relation) {
            $relationInstance = $this->relationInstance($model, $relation);
            if ($relationInstance instanceof BelongsTo) {
                $columns[] = $relationInstance->getForeignKeyName();
            }
        }

        $columns = array_values(array_filter(array_unique($columns)));

        $builder->select($columns);

        $with = [];
        foreach (array_unique(array_merge($relations, array_keys($relationFields))) as $relation) {
            $relationInstance = $this->relationInstance($model, $relation);
            if (!$relationInstance) {
                continue;
            }

            $requestedFields = $relationFields[$relation] ?? [];
            $fields = $this->relationSelectFields($relationInstance, $requestedFields);

            $with[$relation] = function ($q) use ($fields) {
                $q->select($fields);
            };
        }

        if (!empty($with)) {
            $builder->with($with);
        }

        // Provide stable ordering for selects.
        $builder->orderBy($nameFields[0] ?? $keyField);

        // If caller explicitly sends pagination params, keep list bounded.
        if ($request->filled('per_page') || $request->filled('page')) {
            return collect($builder->paginate()->items());
        }

        return $builder->get();
    }

    /**
     * @return array{0: string[], 1: string[], 2: array<string, string[]>}
     */
    private function parseInfo(Model $model, array $info): array
    {
        $allowedScalar = array_values(array_filter(array_unique(array_merge(
            [$model->getKeyName()],
            $model->getFillable()
        ))));

        $allowed = [];
        $relations = [];
        $relationFields = [];

        foreach ($info as $item) {
            if (!is_string($item) || $item === '') {
                continue;
            }

            if (str_contains($item, '.')) {
                [$relation, $field] = explode('.', $item, 2);
                if ($relation !== '' && $field !== '' && $this->relationInstance($model, $relation)) {
                    $relationFields[$relation] ??= [];
                    $relationFields[$relation][] = $field;
                }

                continue;
            }

            if ($this->isRelationName($model, $item)) {
                $relations[] = $item;
                continue;
            }

            // Scalar field -> include only if fillable or primary key
            if (in_array($item, $allowedScalar, true)) {
                $allowed[] = $item;
            }
        }

        return [
            array_values(array_unique($allowed)),
            array_values(array_unique($relations)),
            collect($relationFields)->map(fn ($v) => array_values(array_unique($v)))->toArray(),
        ];
    }

    private function isRelationName(Model $model, string $name): bool
    {
        return (bool) $this->relationInstance($model, $name);
    }

    private function relationInstance(Model $model, string $name): ?Relation
    {
        if (!method_exists($model, $name)) {
            return null;
        }

        try {
            $relation = $model->{$name}();
            return ($relation instanceof Relation) ? $relation : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @return string[]
     */
    private function relationSelectFields(Relation $relation, array $requestedFields): array
    {
        $related = $relation->getRelated();

        $allowedRelated = array_values(array_filter(array_unique(array_merge(
            [$related->getKeyName()],
            $related->getFillable()
        ))));

        $fields = [];
        $fields[] = $related->getKeyName();

        // Respect requested fields (best-effort); if empty, default to 'name' when available.
        if (!empty($requestedFields)) {
            foreach ($requestedFields as $f) {
                if (is_string($f) && $f !== '' && in_array($f, $allowedRelated, true)) {
                    $fields[] = $f;
                }
            }
        } else {
            if (in_array('name', $related->getFillable(), true)) {
                $fields[] = 'name';
            }
        }

        // Include keys required for relationship mapping.
        if ($relation instanceof HasOneOrMany) {
            $fields[] = $relation->getForeignKeyName();
        }

        $fields = array_values(array_filter(array_unique($fields)));

        return $fields;
    }
}
