<?php

namespace Tests\Unit\Http\Queries\Criteria;

use Tests\TestCase;
use App\Http\Queries\Criteria\BaseCriteria;
use Illuminate\Database\Eloquent\Builder;

class BaseCriteriaTest extends TestCase
{
    protected BaseCriteria $criteria;

    protected function setUp(): void
    {
        parent::setUp();

        $this->criteria = new class extends BaseCriteria {
            public function apply(Builder $query, array $data): Builder
            {
                return $query;
            }

            public function publicGetData(array $data, string $key, mixed $default = null): mixed
            {
                return $this->getData($data, $key, $default);
            }

            public function publicHasData(array $data, string $key): bool
            {
                return $this->hasData($data, $key);
            }

            public function publicIsEmpty(mixed $value): bool
            {
                return $this->isEmpty($value);
            }
        };
    }

    /**
     * Test getData retrieves value.
     */
    public function test_get_data(): void
    {
        $data = ['name' => 'John', 'email' => 'john@example.com'];
        $result = $this->criteria->publicGetData($data, 'name');

        $this->assertEquals('John', $result);
    }

    /**
     * Test getData returns default.
     */
    public function test_get_data_with_default(): void
    {
        $data = ['name' => 'John'];
        $result = $this->criteria->publicGetData($data, 'email', 'default@example.com');

        $this->assertEquals('default@example.com', $result);
    }

    /**
     * Test hasData detects existing key.
     */
    public function test_has_data_true(): void
    {
        $data = ['name' => 'John'];
        $result = $this->criteria->publicHasData($data, 'name');

        $this->assertTrue($result);
    }

    /**
     * Test hasData returns false for missing key.
     */
    public function test_has_data_false(): void
    {
        $data = ['name' => 'John'];
        $result = $this->criteria->publicHasData($data, 'email');

        $this->assertFalse($result);
    }

    /**
     * Test isEmpty detects empty values.
     */
    public function test_is_empty(): void
    {
        $this->assertTrue($this->criteria->publicIsEmpty(null));
        $this->assertTrue($this->criteria->publicIsEmpty(''));
        $this->assertTrue($this->criteria->publicIsEmpty([]));
        $this->assertFalse($this->criteria->publicIsEmpty('value'));
    }
}
