<?php

declare(strict_types=1);

namespace Prestoworld\Bridge\WordPress\Admin;

use Witals\Framework\Application;

class AdminRenderer
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Render a callback in a hybrid environment (WordPress + PrestoWorld)
     */
    public function renderHybrid(callable $callback, array $args = []): string
    {
        ob_start();
        try {
            // Ensure WP globals are available if possible
            $this->prepareWpGlobals();
            
            // Execute the callback
            $result = $callback(...$args);
            
            // If callback returns a string, use it. If it echoes, ob_get_clean will catch it.
            $output = ob_get_clean();
            
            return is_string($result) ? $result : $output;
        } catch (\Throwable $e) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            return "Admin Rendering Error: " . $e->getMessage();
        }
    }

    protected function prepareWpGlobals(): void
    {
        global $wp, $wp_query, $wpdb, $current_user;
        // Mocking some basic WP globals if they don't exist
        if (!isset($current_user)) {
             $current_user = (object)[
                 'ID' => 1,
                 'user_login' => 'admin',
                 'display_name' => 'Administrator',
                 'roles' => ['administrator']
             ];
        }
    }
}
