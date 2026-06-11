<?php

namespace App\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

abstract class SelectItemRequest extends BaseRequest
{
    /**
     * Set this in child request.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected string $model = '';

    /**
     * @return class-string<Model>
     */
    public function modelClass(): string
    {
        $model = $this->model;

        if (!is_string($model) || $model === '' || !class_exists($model) || !is_subclass_of($model, Model::class)) {
            throw new \RuntimeException(static::class . ': you must set protected string $model to a valid Eloquent model class.');
        }

        return $model;
    }

    public function modelInstance(): Model
    {
        $modelClass = $this->modelClass();
        return new $modelClass();
    }

    /**
     * Allowed scalar fields used for key/name/info.
     * We allow the model primary key even if it is not fillable.
     */
    public function allowedScalarFields(): array
    {
        $model = $this->modelInstance();

        $fields = array_values(array_filter(array_unique(array_merge(
            [$model->getKeyName()],
            $model->getFillable()
        )), fn ($v) => is_string($v) && $v !== ''));

        return $fields;
    }

    public function keyField(): string
    {
        return (string) $this->input('key');
    }

    /** @return string[] */
    public function nameFields(): array
    {
        return self::normalizeFieldList($this->input('name'));
    }

    /** @return string[] */
    public function infoFields(): array
    {
        return self::normalizeFieldList($this->input('info'));
    }

    /**
     * Normalize select field list input.
     *
     * String => single field label; array => combine multiple fields (existing behavior).
     *
     * @return string[]
     */
    public static function normalizeFieldList(mixed $value): array
    {
        if (is_string($value) && $value !== '') {
            return [$value];
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, fn ($v) => is_string($v) && $v !== ''));
    }

    public function rules(): array
    {
        $allowed = $this->allowedScalarFields();

        return [
            // Same shape as search: filter[field]=value
            'filter' => ['nullable', 'array'],
            'filter.*' => ['nullable'],

            // Key field for v-model value
            'key' => ['required', 'string', Rule::in($allowed)],

            // Name: one field (string) or combined label (array)
            'name' => ['required', $this->scalarFieldListRule($allowed, minItems: 1)],

            // Optional extra fields/relations; invalid items will be ignored by query layer
            'info' => ['nullable', $this->scalarFieldListRule(allowed: null, minItems: 0)],

            // Optional pagination if caller wants (kept compatible with BaseQuery)
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @param  string[]|null  $allowed  When set, each field must be in this list.
     */
    protected function scalarFieldListRule(?array $allowed, int $minItems): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($allowed, $minItems): void {
            if (is_string($value)) {
                if ($value === '') {
                    $fail(__('validation.min.array', ['attribute' => $attribute, 'min' => max(1, $minItems)]));

                    return;
                }

                if ($allowed !== null && ! in_array($value, $allowed, true)) {
                    $fail(__('validation.in', ['attribute' => $attribute]));
                }

                return;
            }

            if (! is_array($value)) {
                $fail(__('validation.array', ['attribute' => $attribute]));

                return;
            }

            $items = array_values(array_filter($value, fn ($v) => is_string($v) && $v !== ''));

            if (count($items) < $minItems) {
                $fail(__('validation.min.array', ['attribute' => $attribute, 'min' => max(1, $minItems)]));

                return;
            }

            if ($allowed === null) {
                return;
            }

            foreach ($items as $index => $item) {
                if (! in_array($item, $allowed, true)) {
                    $fail(__('validation.in', ['attribute' => "{$attribute}.{$index}"]));
                }
            }
        };
    }
}
