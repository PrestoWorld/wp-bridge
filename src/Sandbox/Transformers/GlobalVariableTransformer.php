<?php

declare(strict_types=1);

namespace Prestoworld\Bridge\WordPress\Sandbox\Transformers;

/**
 * Class GlobalVariableTransformer
 * 
 * Rewrites access to WordPress global variables (like $wp_version) 
 * to use the GlobalManager from the container.
 */
class GlobalVariableTransformer implements TransformerInterface
{
    public function getPriority(): int
    {
        return 110; // Run before GlobalToContainerTransformer
    }

    public function transform(string $source): string
    {
        // Transform $GLOBALS['wp_version'] -> app('global')->get('wp_version')
        $source = preg_replace(
            '/\$GLOBALS\s*\[\s*[\'"](wp_version|wp_db_version|tinymce_version|wp_local_package)[\'"]\s*\]/',
            'app(\'global\')->get(\'$1\')',
            $source
        );

        return $source;
    }
}
