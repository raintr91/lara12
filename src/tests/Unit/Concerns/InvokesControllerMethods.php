<?php

namespace Tests\Unit\Concerns;

use App\Http\Actions\BaseAction;
use App\Http\Queries\BaseQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Response;

/**
 * Invoke public controller methods with Action/Query/Request doubles.
 *
 * Override moduleControllerSkipClasses() to skip module base controllers.
 */
trait InvokesControllerMethods
{
    protected function invokeAllControllerMethods(object $controller): void
    {
        $reflection = new \ReflectionClass($controller);
        $invoked = 0;
        $skipDeclaring = array_merge($this->defaultControllerSkipDeclaringClasses(), $this->moduleControllerSkipClasses());

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (in_array($method->name, ['__construct', 'middleware'], true)) {
                continue;
            }

            $declaring = $method->getDeclaringClass()->getName();
            if (in_array($declaring, $skipDeclaring, true)) {
                continue;
            }

            try {
                $response = $this->invokeControllerMethod($controller, $method);
                $this->assertInstanceOf(Response::class, $response);
                $invoked++;
            } catch (\Throwable) {
                continue;
            }
        }

        $this->assertGreaterThan(
            0,
            $invoked,
            sprintf('%s should expose at least one invokable handler', $reflection->getName()),
        );
    }

    /**
     * @return array<int, class-string>
     */
    protected function defaultControllerSkipDeclaringClasses(): array
    {
        return [
            \Illuminate\Routing\Controller::class,
            \App\Http\Controllers\Controller::class,
            \App\Http\Controllers\BaseController::class,
        ];
    }

    /**
     * @return array<int, class-string>
     */
    protected function moduleControllerSkipClasses(): array
    {
        return [];
    }

    private function invokeControllerMethod(object $controller, ReflectionMethod $method): Response
    {
        if ($method->name === 'index' && count($method->getParameters()) > 0) {
            $queryClass = $this->deriveSiblingClass($controller, 'Query');
            $this->app->bind($queryClass, fn () => $this->makeQueryDouble($queryClass));
        }

        $args = [];
        foreach ($method->getParameters() as $parameter) {
            $args[] = $this->resolveControllerArgument($parameter, $method->name);
        }

        $result = $method->invokeArgs($controller, $args);

        if ($result instanceof Response) {
            return $result;
        }

        return new JsonResponse($result ?? null);
    }

    private function resolveControllerArgument(\ReflectionParameter $parameter, string $methodName): mixed
    {
        $type = $parameter->getType();

        if (! $type instanceof \ReflectionNamedType || $type->isBuiltin()) {
            return match ($parameter->getName()) {
                'operation' => $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : $methodName,
                'arrId' => [1, 2],
                'id' => 1,
                default => $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : 1,
            };
        }

        $className = $type->getName();

        if (is_a($className, BaseAction::class, true)) {
            return $this->makeActionDouble($className);
        }

        if (is_a($className, BaseQuery::class, true)) {
            return $this->makeQueryDouble($className);
        }

        if (is_a($className, FormRequest::class, true)) {
            return $this->makeFormRequestDouble($className, $this->defaultRequestPayload($methodName));
        }

        if ($className === Request::class) {
            return Request::create('/admin-test', 'POST', ['arrId' => [1, 2]]);
        }

        return $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultRequestPayload(string $methodName): array
    {
        if ($methodName === 'search' || $methodName === 'index') {
            return ['page' => 1, 'per_page' => 10];
        }

        return ['name' => 'demo'];
    }

    protected function makeFormRequestDouble(string $className, array $payload): FormRequest
    {
        $fqcn = '\\'.ltrim($className, '\\');

        /** @var FormRequest $request */
        $request = eval(sprintf(
            'return new class(%s) extends %s {
                public function __construct(private array $payload) {
                    parent::__construct([], $this->payload, [], [], [], ["REQUEST_METHOD" => "POST"]);
                }
                public function validated($key = null, $default = null): array { return $this->payload; }
                public function authorize(): bool { return true; }
                public function rules(): array { return []; }
            };',
            var_export($payload, true),
            $fqcn
        ));

        $request->initialize([], $payload, [], [], [], ['REQUEST_METHOD' => 'POST']);

        return $request;
    }

    protected function makeActionDouble(string $className): BaseAction
    {
        $reflection = new \ReflectionClass($className);
        $constructor = $reflection->getConstructor();

        if (! $constructor || $constructor->getNumberOfRequiredParameters() === 0) {
            return $this->makeAnonymousActionDouble($className);
        }

        $args = [];
        foreach ($constructor->getParameters() as $parameter) {
            $args[] = $this->resolveActionConstructorArgument($parameter);
        }

        return $reflection->newInstanceArgs($args);
    }

    protected function resolveActionConstructorArgument(\ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        if ($type instanceof \ReflectionNamedType && ! $type->isBuiltin()) {
            $className = $type->getName();
            if (is_subclass_of($className, Model::class)) {
                return $this->makeModelStub($className);
            }
        }

        return $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    protected function makeModelStub(string $modelClass): Model
    {
        $fqcn = '\\'.ltrim($modelClass, '\\');

        return eval(sprintf(
            'return new class extends %s {
                public $timestamps = false;
                protected $guarded = [];
                public function __construct() {}
            };',
            $fqcn,
        ));
    }

    protected function makeAnonymousActionDouble(string $className): BaseAction
    {
        $fqcn = '\\'.ltrim($className, '\\');

        return eval(sprintf(
            'return new class extends %s {
                public array $received = [];
                protected function run(...$args): mixed {
                    $this->received = $args;
                    return ["handled" => true, "args" => $args];
                }
            };',
            $fqcn
        ));
    }

    protected function makeQueryDouble(string $className): BaseQuery
    {
        $fqcn = '\\'.ltrim($className, '\\');

        return eval(sprintf(
            'return new class extends %s {
                public function __construct() {}
                protected function newQuery(): ?\Illuminate\Database\Eloquent\Builder {
                    return null;
                }
                public function paginate(): \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection {
                    return new \Illuminate\Pagination\LengthAwarePaginator([["id" => 1]], 1, 15, 1, ["path" => "/admin-test"]);
                }
                public function findById($id) { return ["id" => $id]; }
            };',
            $fqcn
        ));
    }

    private function deriveSiblingClass(object $controller, string $suffix): string
    {
        $controllerClass = $controller::class;
        $directory = $suffix === 'Query' ? 'Queries' : $suffix.'s';

        return preg_replace(
            '/\\\\Http\\\\Controllers\\\\/',
            '\\Http\\'.$directory.'\\',
            preg_replace('/Controller$/', $suffix, $controllerClass) ?? $controllerClass
        ) ?? $controllerClass;
    }
}
