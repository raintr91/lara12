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
        $name = $this->input('name', []);
        if (!is_array($name)) {
            return [];
        }

        return array_values(array_filter($name, fn ($v) => is_string($v) && $v !== ''));
    }

    /** @return string[] */
    public function infoFields(): array
    {
        $info = $this->input('info', []);
        if (!is_array($info)) {
            return [];
        }

        return array_values(array_filter($info, fn ($v) => is_string($v) && $v !== ''));
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

            // Name fields for display label (allow concat multiple fields)
            'name' => ['required', 'array', 'min:1'],
            'name.*' => ['required', 'string', Rule::in($allowed)],

            // Optional extra fields/relations; invalid items will be ignored by query layer
            'info' => ['nullable', 'array'],
            'info.*' => ['nullable', 'string'],

            // Optional pagination if caller wants (kept compatible with BaseQuery)
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
