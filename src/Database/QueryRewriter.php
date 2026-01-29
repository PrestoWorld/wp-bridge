<?php

declare(strict_types=1);

namespace Prestoworld\Bridge\WordPress\Database;

use Cycle\Database\Query\SelectQuery;

/**
 * Class QueryRewriter
 * 
 * Intercepts WordPress Legacy SQL queries (using WP_Query/get_posts)
 * and rewrites them to optimized CycleORM/SQL queries targeting native tables.
 * 
 * Example:
 * SELECT * FROM wp_posts WHERE post_type = 'product'
 * -> Becomes -> 
 * SELECT * FROM wp_products WHERE type = 'simple' ...
 */
class QueryRewriter
{
    /**
     * Rewrite rules mapping WP tables to Presto Optimized Tables
     */
    protected array $tableMap = [
        'wp_posts' => [
            'product' => 'wp_products',  // WooCommerce products -> Native Products Table
            'shop_order' => 'wp_orders', // Orders -> Native Orders Table
        ],
        'wp_postmeta' => [
            'product' => 'wp_product_meta',
            'shop_order' => 'wp_order_meta'
        ]
    ];

    public function rewrite(string $sql, array $bindings = []): array
    {
        // 1. Detect Intent: Is this a Product Query?
        if ($this->isProductQuery($sql, $bindings)) {
            return $this->rewriteProductQuery($sql, $bindings);
        }

        // 2. Detect Intent: Is this an Order Query?
        if ($this->isOrderQuery($sql, $bindings)) {
             return $this->rewriteOrderQuery($sql, $bindings);
        }

        // Default: No rewrite
        return [$sql, $bindings];
    }

    protected function isProductQuery(string $sql, array $bindings): bool
    {
        // Simple heuristic: Look for post_type = 'product'
        // In real regex, we'd be more robust.
        return str_contains($sql, "post_type = 'product'") || 
               str_contains($sql, "post_type = ?") && in_array('product', $bindings);
    }
    
    protected function isOrderQuery(string $sql, array $bindings): bool
    {
        return str_contains($sql, "post_type = 'shop_order'") || 
               str_contains($sql, "post_type = ?") && in_array('shop_order', $bindings);
    }

    protected function rewriteProductQuery(string $sql, array $bindings): array
    {
        // Logic to transform WP_Query SQL to Native Product SQL
        
        // Step 1: Replace Table Name
        // FROM wp_posts -> FROM wp_products
        $newSql = str_replace('wp_posts', 'wp_products', $sql);
        
        // Step 2: Remove 'post_type' check (Native table implies type)
        $newSql = str_replace("AND post_type = 'product'", "", $newSql);
        
        // Step 3: Column Mapping (if schema differs)
        // post_title -> name
        // post_content -> description
        // (This assumes we have aliases or renamed columns in the new table)
        // For now, let's assume we migrated data 1:1 or use view aliasing.
        
        return [$newSql, $bindings];
    }
    
    protected function rewriteOrderQuery(string $sql, array $bindings): array
    {
        // FROM wp_posts -> FROM wp_orders
        $newSql = str_replace('wp_posts', 'wp_orders', $sql);
        $newSql = str_replace("AND post_type = 'shop_order'", "", $newSql);
        
        return [$newSql, $bindings];
    }
}
