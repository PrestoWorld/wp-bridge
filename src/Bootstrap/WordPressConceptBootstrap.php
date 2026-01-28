<?php

declare(strict_types=1);

namespace Prestoworld\Bridge\WordPress\Bootstrap;

use Witals\Framework\Application;
use PrestoWorld\Hooks\HookManager;

/**
 * WordPress Concept Bootstrap
 * 
 * Simulated core WordPress concepts like hooks lifecycle within PrestoWorld.
 */
class WordPressConceptBootstrap
{
    public function bootstrap(Application $app): void
    {
        // Load WordPress simulation helpers FIRST
        $helpersPath = __DIR__ . '/../helpers.php';
        if (file_exists($helpersPath) && !function_exists('get_permalink')) {
            require_once $helpersPath;
        }

        // Bridge 'init' to application boot
        $app->booted(function() use ($app) {
            $this->doWpAction($app, 'init');
        });

        // Bridge 'wp' and 'pre_get_posts' to the beginning of request handling
        $app->beforeRequest(function() use ($app) {
            $this->doWpAction($app, 'wp');
            $this->doWpAction($app, 'pre_get_posts');
        });
    }

    protected function doWpAction(Application $app, string $hook): void
    {
        if ($app->has(HookManager::class)) {
            $app->make(HookManager::class)->doAction($hook);
        }
    }
}
