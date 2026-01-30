<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Sandbox\Transformers;

/**
 * Class WpOptionsTransformer
 * 
 * The ultimate fix for WP Options mess.
 * It rewrites option and transient functions to use PrestoWorld's high-performance 
 * OptionsManager (Redis/KV backed) instead of original WP implementation.
 */
class WpOptionsTransformer implements TransformerInterface
{
    public function getPriority(): int
    {
        return 90; // High priority, run after global transformations
    }

    public function transform(string $source): string
    {
        // 1. Rewrite get_option/update_option/delete_option
        // Transform: get_option('my_opt', 'default') -> app('wp.options')->get('my_opt', 'default')
        $source = preg_replace(
            '/\b(get_option|update_option|add_option|delete_option)\s*\(/',
            'app(\'wp.options\')->$1(',
            $source
        );

        // 2. Rewrite transients (now supported by OptionsManager)
        // Transform: get_transient('my_trans') -> app('wp.options')->get_transient('my_trans')
        $source = preg_replace(
            '/\bget_transient\s*\(/',
            'app(\'wp.options\')->get_transient(',
            $source
        );

        $source = preg_replace(
            '/\bset_transient\s*\(/',
            'app(\'wp.options\')->set_transient(',
            $source
        );

        $source = preg_replace(
            '/\bdelete_transient\s*\(/',
            'app(\'wp.options\')->delete_transient(',
            $source
        );

        // 3. Intercept site_option (Multisite version)
        $source = preg_replace(
            '/\b(get_site_option|update_site_option)\s*\(/',
            'app(\'wp.options\')->$1(',
            $source
        );

        return $source;
    }
}
