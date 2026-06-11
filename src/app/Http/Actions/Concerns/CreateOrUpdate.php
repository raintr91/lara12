<?php

namespace App\Http\Actions\Concerns;

use Illuminate\Database\Eloquent\Model;

trait CreateOrUpdate
{
    public function newQuery()
    {
        return $this->model::query();
    }

    /**
     * Create or update by conditions
     */
    protected function createOrUpdateBy(
        array $conditions,
        array $data
    ): Model {
        return $this->transaction(function () use ($conditions, $data) {
            return $this->newQuery()->updateOrCreate(
                $conditions,
                $data
            );
        });
    }

    /**
     * Find by id or fail
     */
    protected function findOrFail(int|string $id): Model
    {
        return $this->newQuery()->findOrFail($id);
    }

    /**
     * Update by id
     */
    protected function updateById(int|string $id, array $data): Model
    {
        return $this->transaction(function () use ($id, $data) {
            $model = $this->findOrFail($id);
            $model->update($data);

            return $model->refresh();
        });
    }

    /**
     * Delete by id
     */
    protected function deleteById(int|string $id): bool
    {
        return $this->transaction(function () use ($id) {
            return $this->findOrFail($id)->delete();
        });
    }
}
