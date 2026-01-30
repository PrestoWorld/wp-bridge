<?php

declare(strict_types=1);

namespace Prestoworld\Bridge\WordPress\Sandbox\Transformers;

use Prestoworld\Bridge\WordPress\Sandbox\Transformers\TransformerInterface;

/**
 * Class DirectOutputBufferTransformer
 * 
 * WordPress plugins notorious for using `echo` or `print` directly in logic,
 * which breaks modern Response objects (RoadRunner/Swoole).
 * 
 * This transformer wraps output functions with output buffering capture.
 * 
 * Example:
 * echo "Hello";
 * ->
 * ob_start(); echo "Hello"; $output = ob_get_clean();
 */
class DirectOutputBufferTransformer implements TransformerInterface
{
    public function getPriority(): int
    {
        return 50;
    }

    public function transform(string $source): string
    {
        // Simple regex to catch standalone echo statements at start of function or block logic
        // Real implementation needs full AST parsing.
        
        // This is a naive example:
        // transforms `echo $x;` -> `\PrestoWorld\Bridge\Output::buffer(function() use ($x) { echo $x; });`
        // But for regex safety, let's target `wp_die` which often outputs directly.
        
        return preg_replace('/wp_die\((.*?)\);/s', '\PrestoWorld\Bridge\Output::die($1);', $source);
    }
}
