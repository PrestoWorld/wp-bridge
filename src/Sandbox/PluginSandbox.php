<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Sandbox;

use PrestoWorld\Contracts\Sandbox\SandboxInterface;
use PrestoWorld\Contracts\Sandbox\TransformerInterface;

class PluginSandbox implements SandboxInterface
{
    /** @var TransformerRegistry */
    protected TransformerRegistry $registry;

    protected string $cachePath;

    public function __construct(string $cachePath, TransformerRegistry $registry)
    {
        $this->cachePath = rtrim($cachePath, '/');
        $this->registry = $registry;
        
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }

    public function addTransformer(TransformerInterface $transformer): void
    {
        // No longer used directly, we use the registry
    }

    public function execute(string $file, array $context = []): mixed
    {
        $compiledFile = $this->compile($file);

        return $this->evaluate($compiledFile, $context);
    }

    protected function compile(string $file): string
    {
        $cacheFile = $this->cachePath . '/' . md5($file) . '.php';

        if (file_exists($cacheFile) && filemtime($cacheFile) >= filemtime($file)) {
            return $cacheFile;
        }

        $code = file_get_contents($file);
        
        // Get only relevant transformers for this specific file contents
        $transformers = $this->registry->getTransformersFor($code);

        foreach ($transformers as $transformer) {
            $code = $transformer->transform($code);
        }

        file_put_contents($cacheFile, $code);

        return $cacheFile;
    }

    protected function evaluate(string $__file, array $__context): mixed
    {
        extract($__context);
        
        return include $__file;
    }
}
