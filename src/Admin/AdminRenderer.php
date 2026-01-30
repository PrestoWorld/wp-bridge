<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Admin;

use Witals\Framework\Application;

class AdminRenderer
{
    protected Application $app;
    protected \Witals\Framework\Contracts\Auth\AuthContextInterface $auth;

    public function __construct(Application $app, \Witals\Framework\Contracts\Auth\AuthContextInterface $auth)
    {
        $this->app = $app;
        $this->auth = $auth;
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
            return "Admin Rendering Error: " .$e->getMessage().'<br/><pre>' . $e->getTraceAsString();
        }
    }

    protected function prepareWpGlobals(): void
    {
        global $wp, $wp_query, $wpdb, $current_user;
        
        $actor = $this->auth->getActor();

        // Mocking some basic WP globals if they don't exist
        if (!isset($current_user)) {
             if ($actor) {
                 $current_user = (object)[
                     'ID' => $actor->id ?? 1,
                     'user_login' => $actor->name ?? 'admin',
                     'display_name' => $actor->name ?? 'Administrator',
                     'roles' => $actor->roles ?? ['administrator']
                 ];
             } else {
                 // For safety in admin area, we don't mock it as admin if not logged in
                 // But some WP functions expect $current_user to be set.
                 $current_user = null;
             }
        }
    }

}
