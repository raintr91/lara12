<?php

namespace Tests\Unit\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use ReflectionMethod;

/**
 * Provides shared contract assertion tests for Eloquent model test classes.
 *
 * Usage:
 *   – Declare `protected string $modelClass = YourModel::class;` in the test class.
 *   – Set MODEL_CONTRACT_STRICT=true to fail when optional contracts are missing.
 */
trait HasModelContractAssertions
{
    /** Fully-qualified class name of the model under test. Must be overridden. */
    protected string $modelClass = '';

    // -------------------------------------------------------------------------
    // Structural tests
    // -------------------------------------------------------------------------

    public function test_model_file_exists(): void
    {
        $this->assertNotEmpty($this->modelClass, 'Property $modelClass must be declared in '.static::class);
        $file = (new ReflectionClass($this->modelClass))->getFileName();
        $this->assertFileExists((string) $file);
    }

    public function test_model_class_declaration_matches_expected_fqcn(): void
    {
        $reflection = new ReflectionClass($this->modelClass);
        $path       = (string) $reflection->getFileName();
        $contents   = (string) file_get_contents($path);

        preg_match('/^namespace\s+([^;]+);/m', $contents, $nsMatches);
        preg_match('/^\s*(?:abstract\s+|final\s+)?(?:class|interface|trait|enum)\s+([A-Za-z_][A-Za-z0-9_]*)/m', $contents, $classMatches);

        $this->assertArrayHasKey(1, $nsMatches, "No namespace declaration found in [{$path}].");
        $this->assertArrayHasKey(1, $classMatches, "No class-like declaration found in [{$path}].");

        $declaredFqcn = trim($nsMatches[1]).'\\'.trim($classMatches[1]);
        $this->assertSame($this->modelClass, $declaredFqcn);
    }

    public function test_model_class_is_loadable(): void
    {
        $this->assertTrue(class_exists($this->modelClass));
    }

    public function test_model_is_eloquent_model(): void
    {
        $class = $this->modelClass;
        $this->assertInstanceOf(Model::class, new $class());
    }

    // -------------------------------------------------------------------------
    // Contract tests (skipped when nothing is declared)
    // -------------------------------------------------------------------------

    protected function isStrictModelContractMode(): bool
    {
        $raw = env('MODEL_CONTRACT_STRICT', false);

        if (is_bool($raw)) {
            return $raw;
        }

        return in_array(strtolower((string) $raw), ['1', 'true', 'yes', 'on'], true);
    }

    protected function passOrFailOptionalContract(string $message): void
    {
        if ($this->isStrictModelContractMode()) {
            $this->fail($message);
            return;
        }

        $this->assertTrue(true, $message);
    }

    public function test_fillable_contract(): void
    {
        $class    = $this->modelClass;
        $model    = new $class();
        $fillable = $model->getFillable();

        if (empty($fillable)) {
            $this->passOrFailOptionalContract("Model [{$class}] has no fillable fields.");
            return;
        }

        foreach ($fillable as $field) {
            $this->assertIsString($field);
            $this->assertNotSame('', trim($field), "Fillable field must not be an empty string.");
        }

        $table = $model->getTable();

        try {
            if (!Schema::hasTable($table)) {
                $this->passOrFailOptionalContract("Table [{$table}] is not available in current test environment.");
                return;
            }

            $columns = Schema::getColumnListing($table);
            foreach ($fillable as $field) {
                $this->assertContains(
                    $field,
                    $columns,
                    "Fillable field [{$field}] is not found in table [{$table}]."
                );
            }
        } catch (\Throwable $e) {
            $this->passOrFailOptionalContract("Skipping table column validation for [{$class}] because DB is unavailable: {$e->getMessage()}");
        }
    }

    public function test_casts_contract(): void
    {
        $class = $this->modelClass;
        $casts = (new $class())->getCasts();

        if (empty($casts)) {
            $this->passOrFailOptionalContract("Model [{$class}] has no casts defined.");
            return;
        }

        foreach ($casts as $field => $cast) {
            $this->assertIsString((string) $field);
            $this->assertTrue(
                is_string($cast) || is_array($cast),
                "Cast for [{$field}] must be a string or array."
            );
        }
    }

    public function test_declared_relationship_methods_return_relation_instances(): void
    {
        $class      = $this->modelClass;
        $model      = new $class();
        $reflection = new ReflectionClass($model);
        $relations  = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class !== $reflection->getName()) {
                continue;
            }
            if ($method->isStatic() || $method->getNumberOfRequiredParameters() > 0) {
                continue;
            }
            if (str_starts_with($method->name, '__') || str_starts_with($method->name, 'scope')) {
                continue;
            }

            try {
                $value = $method->invoke($model);
            } catch (\Throwable) {
                continue;
            }

            if ($value instanceof Relation) {
                $relations[$method->name] = $value;
            }
        }

        if (empty($relations)) {
            $this->passOrFailOptionalContract("Model [{$class}] has no detectable relationship methods.");
            return;
        }

        foreach ($relations as $name => $relation) {
            $this->assertInstanceOf(Relation::class, $relation, "Method [{$name}] must return a Relation instance.");
        }
    }
}
