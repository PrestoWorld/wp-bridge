<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Sandbox;

use PrestoWorld\Contracts\Sandbox\TransformerInterface;

class TransformerRegistry
{
    /** @var array<string, class-string<TransformerInterface>[]> */
    protected array $keywordMap = [];

    /** @var TransformerInterface[] */
    protected array $instances = [];

    /**
     * Register a transformer class associated with specific keywords.
     *
     * @param class-string<TransformerInterface> $className
     * @param string[] $keywords
     */
    public function register(string $className, array $keywords): void
    {
        foreach ($keywords as $keyword) {
            $this->keywordMap[$keyword][] = $className;
        }
    }

    /**
     * Get relevant transformers for the given source code.
     *
     * @return TransformerInterface[]
     */
    public function getTransformersFor(string $code): array
    {
        $neededClasses = [];
        
        foreach ($this->keywordMap as $keyword => $classes) {
            if (str_contains($code, $keyword)) {
                foreach ($classes as $class) {
                    $neededClasses[$class] = true;
                }
            }
        }

        $transformers = [];
        foreach (array_keys($neededClasses) as $className) {
            if (!isset($this->instances[$className])) {
                // In a real app, use a Container to resolve dependencies
                $this->instances[$className] = new $className();
            }
            $transformers[] = $this->instances[$className];
        }

        return $transformers;
    }

    public function getAllRegistered(): array
    {
        return $this->keywordMap;
    }
}
