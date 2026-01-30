<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Sandbox\Transformers;

/**
 * Interface TransformerInterface
 * 
 * Defines a code transformer that takes WordPress PHP source code
 * and converts specific legacy patterns into PrestoWorld compatible code.
 */
interface TransformerInterface
{
    /**
     * Get the priority of the transformer.
     * Higher priority transformers run first.
     */
    public function getPriority(): int;

    /**
     * Transform the source code.
     * 
     * @param string $source The original PHP source code
     * @return string The transformed code
     */
    public function transform(string $source): string;
}
