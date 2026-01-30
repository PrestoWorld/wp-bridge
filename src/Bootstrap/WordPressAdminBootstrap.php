<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Bootstrap;

use Witals\Framework\Application;
use PrestoWorld\Hooks\HookManager;

class WordPressAdminBootstrap
{
    public function bootstrap(Application $app): void
    {
        // Define admin constants
        if (!defined('WP_ADMIN')) {
            define('WP_ADMIN', true);
        }

        // Trigger Admin Lifecycle Hooks
        
        // 1. admin_menu: Where plugins register menus
        $this->doWpAction($app, 'admin_menu');

        // 2. admin_init: Core admin initialization (Settings API registration usually happens here)
        $this->doWpAction($app, 'admin_init');

        // 3. current_screen: Setup screen context (Important for get_current_screen())
        $this->doWpAction($app, 'current_screen'); // Requires screen object setup first
    }

    public function runAdminHead(Application $app): void
    {
        $this->doWpAction($app, 'admin_head');
        $this->doWpAction($app, 'admin_enqueue_scripts');
        $this->doWpAction($app, 'admin_print_styles');
        $this->doWpAction($app, 'admin_print_scripts');
    }

    public function runAdminFooter(Application $app): void
    {
        $this->doWpAction($app, 'admin_footer');
        $this->doWpAction($app, 'admin_print_footer_scripts');
    }

    protected function doWpAction(Application $app, string $hook): void
    {
        if ($app->has(HookManager::class)) {
            $app->make(HookManager::class)->doAction($hook);
        }
    }
}
