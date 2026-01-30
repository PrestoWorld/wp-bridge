<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Dashboard;

use PrestoWorld\Admin\Contracts\DashboardDriver;
use App\Http\Routing\Router;
use Witals\Framework\Http\Response;

class WordPressDashboardDriver implements DashboardDriver
{
    public function getName(): string
    {
        return 'wordpress';
    }

    public function isSupported(): bool
    {
        // Check if the WP Bridge package is installed/enabled
        return class_exists(\PrestoWorld\Bridge\WordPress\Providers\WordPressServiceProvider::class);
    }

    public function getRoutePrefix(): string
    {
        return '/wp-admin';
    }

    public function registerRoutes(Router $router): void
    {
        $prefix = $this->getRoutePrefix();

        // Dashboard Home
        $router->get($prefix, [\PrestoWorld\Bridge\WordPress\Http\Controllers\DashboardController::class, 'index']);
        $router->get($prefix . '/', [\PrestoWorld\Bridge\WordPress\Http\Controllers\DashboardController::class, 'index']);
        $router->get($prefix . '/index.php', [\PrestoWorld\Bridge\WordPress\Http\Controllers\DashboardController::class, 'index']);

        // Dynamic Admin Pages (edit.php, options-general.php, etc.)
        $router->get($prefix . '/{page}.php', [\PrestoWorld\Bridge\WordPress\Http\Controllers\DashboardController::class, 'show']);
        
        // Options Page Handling (Simulated options.php)
        $router->post($prefix . '/options.php', [\PrestoWorld\Bridge\WordPress\Http\Controllers\DashboardController::class, 'saveOptions']);
        
        // Admin Ajax
        $router->post($prefix . '/admin-ajax.php', [\PrestoWorld\Bridge\WordPress\Http\Controllers\DashboardController::class, 'ajax']);
        $router->get($prefix . '/admin-ajax.php', [\PrestoWorld\Bridge\WordPress\Http\Controllers\DashboardController::class, 'ajax']);

        // Catch-all for any other admin .php files
        $router->get($prefix . '/{any}', [\PrestoWorld\Bridge\WordPress\Http\Controllers\DashboardController::class, 'show'])
               ->where('any', '.*\.php$');

        // Login Routes
        $router->get('/wp-login.php', [\PrestoWorld\Bridge\WordPress\Http\Controllers\LoginController::class, 'show']);
        $router->post('/wp-login.php', [\PrestoWorld\Bridge\WordPress\Http\Controllers\LoginController::class, 'login']);
    }
}

