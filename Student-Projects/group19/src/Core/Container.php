<?php

declare(strict_types=1);

namespace App\Core;

use ReflectionClass;
use ReflectionNamedType;
use Exception;

/**
 * Modern DI Container with Auto-wiring.
 * Optimized for PHP 8.3 performance.
 */
class Container
{
    /** @var array<string, object> Singletons storage */
    private array $instances = [];

    /**
     * Resolve the given type from the container.
     * * @template T
     * @param class-string<T> $id
     * @return T
     * @throws Exception
     */
    public function get(string $id): object
    {
        // 1. If we already have an instance, return it (Singleton Pattern)
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        return $this->resolve($id);
    }

    /**
     * Advanced Auto-wiring logic using Reflection.
     * * @param string $id
     * @return object
     * @throws Exception
     */
    private function resolve(string $id): object
    {
        // 2. Reflect on the class to inspect its constructor
        $reflectionClass = new ReflectionClass($id);

        if (!$reflectionClass->isInstantiable()) {
            throw new Exception("Class {$id} is not instantiable.");
        }

        $constructor = $reflectionClass->getConstructor();

        // 3. If no constructor, just instantiate and store
        if (null === $constructor) {
            return $this->instances[$id] = new $id();
        }

        // 4. Inspect constructor parameters for dependencies
        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                    continue;
                }
                throw new Exception("Cannot resolve parameter '{$parameter->getName()}' in class {$id}");
            }

            // 5. Recursively resolve dependencies
            $dependencies[] = $this->get($type->getName());
        }

        // 6. Create instance with resolved dependencies
        return $this->instances[$id] = $reflectionClass->newInstanceArgs($dependencies);
    }
}