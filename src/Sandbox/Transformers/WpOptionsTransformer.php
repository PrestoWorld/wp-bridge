<?php

declare(strict_types=1);

namespace Prestoworld\Bridge\WordPress\Sandbox\Transformers;

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

        // 2. Rewrite transients (move to high-performance Cache service directly)
        // Transform: get_transient('my_trans') -> app('cache')->get('_wp_transient_my_trans')
        $source = preg_replace(
            '/\bget_transient\s*\((.+)\)/U',
            'app(\'cache\')->get(\'_wp_t_\' . $1)',
            $source
        );

        $source = preg_replace(
            '/\bset_transient\s*\((.+),\s*(.+),\s*(.+)\)/U',
            'app(\'cache\')->put(\'_wp_t_\' . $1, $2, $3)',
            $source
        );

        $source = preg_replace(
            '/\bdelete_transient\s*\((.+)\)/U',
            'app(\'cache\')->forget(\'_wp_t_\' . $1)',
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
