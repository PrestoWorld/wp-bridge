<?php

declare(strict_types=1);

namespace Prestoworld\Bridge\WordPress\Dashboard;

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
        return class_exists(\Prestoworld\Bridge\WordPress\Providers\WordPressServiceProvider::class);
    }

    public function getRoutePrefix(): string
    {
        return '/wp-admin';
    }

    public function registerRoutes(Router $router): void
    {
        $prefix = $this->getRoutePrefix();

        // Dashboard Home
        $router->get($prefix, function () {
            // Immutability: Admin context
            $GLOBALS['__presto_admin_context'] = ['driver' => 'wordpress', 'screen' => 'dashboard'];
            
            // In a real implementation, this would render a view similar to WP Admin
            return Response::html('<h1>Simulated WP Dashboard</h1><p>Welcome to your simulated WordPress admin panel.</p>');
        });

        // Options Page Handling (Simulated options.php)
        $router->post($prefix . '/options.php', function ($request) {
            // Handle simulated settings saving
            // Verify capabilities, nonces, etc.
            
            // Redirect back to referring page
            return Response::redirect($request->header('referer') ?? '/wp-admin');
        });
    }
}
