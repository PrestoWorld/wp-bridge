<?php

declare(strict_types=1);

namespace Prestoworld\Bridge\WordPress\Sandbox\Transformers;

/**
 * Class GlobalToContainerTransformer
 * 
 * Rewrites "global $var" declarations to fetch from the Application Container.
 * This removes reliance on actual PHP global state, making the code cleaner and easier to test.
 * 
 * Example:
 * global $wpdb;
 * ->
 * $wpdb = \PrestoWorld\Container::get('wpdb');
 */
class GlobalToContainerTransformer implements TransformerInterface
{
    public function getPriority(): int
    {
        return 100;
    }

    protected array $serviceMap = [
        'wpdb' => \Cycle\Database\DatabaseInterface::class, // Map $wpdb to CycleDB
        'wp_rewrite' => \PrestoWorld\Routing\Router::class, // Map rewrite to Router
        'wp_query' => \PrestoWorld\Http\Request::class,     // Map query to Request
        'current_user' => \Witals\Framework\Auth\AuthContextInterface::class, // Map user to Auth
    ];

    public function transform(string $source): string
    {
        // 1. Identify "global $x" lines
        return preg_replace_callback('/global\s+([$a-zA-Z0-9_, $]+);/m', function($matches) {
            $vars = explode(',', $matches[1]);
            $replacements = [];
            
            foreach ($vars as $var) {
                $varName = trim($var); // e.g. $wpdb
                $key = ltrim($varName, '$'); // wpdb
                
                // 2. Check Service Map
                if (isset($this->serviceMap[$key])) {
                    // It's a mapped service -> Resolve from Container with Type Hint
                    // $wpdb = app(DatabaseInterface::class);
                    $serviceId = $this->serviceMap[$key];
                    $replacements[] = "{$varName} = app('{$serviceId}');";
                } else {
                    // 3. Fallback: Generic Global Container Access
                    // $post = app('global')->get('post');
                    $replacements[] = "{$varName} = app('global')->get('{$key}');";
                }
            }
            return implode("\n", $replacements);
        }, $source);
    }
}
