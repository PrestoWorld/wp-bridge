<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Sandbox\Transformers;

use PrestoWorld\Contracts\Sandbox\TransformerInterface;

class WordPressTransformer implements TransformerInterface
{
    /**
     * List of functions that are already provided by wp-bridge (shims).
     * These should NOT be transformed.
     *
     * @var string[]
     */
    protected array $shimmedFunctions = [
        'add_action',
        'do_action',
        'apply_filters',
        'add_filter',
        'get_option',
        'update_option',
        'add_menu_page',
        'wp_die',
        // Add more shimmed functions here
    ];

    public function transform(string $code): string
    {
        // 1. Convert WP global variables to PrestoWorld context if needed
        $code = $this->transformGlobals($code);

        // 2. Transform specific WP patterns that are NOT shimmed
        // Example: converting certain hooks or legacy patterns
        $code = $this->transformLegacyPatterns($code);

        return $code;
    }

    public function supports(string $pattern): bool
    {
        // If it's a shimmed function, we don't support (need) transforming it
        if (in_array($pattern, $this->shimmedFunctions, true)) {
            return false;
        }

        return true;
    }

    protected function transformGlobals(string $code): string
    {
        // Example: Replace global $wpdb with PrestoWorld's DB service call
        // This is a simple regex example, a real one would use a proper parser
        return str_replace('global $wpdb;', '$wpdb = \PrestoWorld\Foundation\DB::getInstance();', $code);
    }

    protected function transformLegacyPatterns(string $code): string
    {
        // Add transformation logic for patterns that aren't shimmed
        return $code;
    }
}
