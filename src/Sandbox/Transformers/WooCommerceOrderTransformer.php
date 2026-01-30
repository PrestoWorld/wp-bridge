<?php

declare(strict_types=1);

namespace Prestoworld\Bridge\WordPress\Sandbox\Transformers;

use Prestoworld\Bridge\WordPress\Sandbox\Transformers\TransformerInterface;
use Prestoworld\Bridge\WordPress\Sandbox\RequirementInterface;
use Prestoworld\Bridge\WordPress\Sandbox\Requirements\PluginRequirement;

/**
 * Class WooCommerceOrderTransformer
 * 
 * Specific transformer for WooCommerce to optimize order fetching.
 * Only runs if context is WooCommerce.
 */
class WooCommerceOrderTransformer implements TransformerInterface
{
    public function getPriority(): int
    {
        return 80;
    }
    
    public function getRequirements(): array
    {
        return [
            new PluginRequirement('woocommerce', '>=', '3.0')
        ];
    }

    public function transform(string $source): string
    {
        // WooCommerce often uses wc_get_orders() which wraps WP_Query.
        // We want to intercept direct SQL in older gateways.
        
        // Example: converting direct postmeta access to Getter
        // update_post_meta($order_id, '_transaction_id', ...) 
        // -> $order->set_transaction_id(...)
        
        return str_replace("update_post_meta(\$order_id, '_transaction_id'", "\$order->set_transaction_id(", $source);
    }
}
