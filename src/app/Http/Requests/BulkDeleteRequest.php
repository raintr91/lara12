<?php

namespace App\Http\Requests;

abstract class BulkDeleteRequest extends BaseRequest
{
    protected int $maxIds = 100;

    public function getMaxIds(): int
    {
        return $this->maxIds;
    }

    public function rules(): array
    {
        return array_merge($this->idsRules(), $this->extraRules());
    }

    protected function idsRules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:'.$this->maxIds],
            'ids.*' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function extraRules(): array
    {
        return [];
    }

    /**
     * @return list<int>
     */
    public function ids(): array
    {
        return array_map(intval(...), $this->validated('ids'));
    }
}
