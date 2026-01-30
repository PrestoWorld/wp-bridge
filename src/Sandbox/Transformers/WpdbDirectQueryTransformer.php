<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Sandbox\Transformers;

use PrestoWorld\Bridge\WordPress\Sandbox\Transformers\TransformerInterface;

/**
 * Class WpdbDirectQueryTransformer
 * 
 * Intercepts direct `$wpdb->query` calls that might try to write to restricted tables
 * and routes them through the Proxy Safe method.
 */
class WpdbDirectQueryTransformer implements TransformerInterface
{
    public function getPriority(): int
    {
        return 90;
    }

    public function transform(string $source): string
    {
        // Finds: $wpdb->query("DELETE FROM ...");
        // Replaces with: $wpdb->safe_query("DELETE FROM ...");
        
        return str_replace('$wpdb->query(', '$wpdb->safe_query(', $source);
    }
}
