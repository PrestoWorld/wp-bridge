<?php

declare(strict_types=1);

namespace Prestoworld\Bridge\WordPress;

use Witals\Framework\Application;

/**
 * WordPress Loader
 */
class WordPressLoader
{
    private Application $app;
    private string $wpPath;
    private bool $loaded = false;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->wpPath = $app->basePath('public');
    }

    public function load(): bool
    {
        if ($this->loaded) {
            return true;
        }

        // Always load helpers (Shims) explicitly to ensure compatibility
        require_once __DIR__ . '/helpers.php';

        if (!file_exists($this->wpPath . '/wp-load.php')) {
            // Clean-room Mode: Simulate WordPress environment
            $this->defineWordPressConstants();
            
            // Define WP_CONTENT_DIR/URL if not defined by constants
            if (!defined('WP_CONTENT_DIR')) {
                define('WP_CONTENT_DIR', $this->wpPath . '/wp-content');
            }
            if (!defined('WP_CONTENT_URL')) {
                define('WP_CONTENT_URL', '/wp-content');
            }

            // Load Mu-Plugins
            $muPluginsDir = WP_CONTENT_DIR . '/mu-plugins';
            if (is_dir($muPluginsDir)) {
                $files = glob($muPluginsDir . '/*.php');
                foreach ($files as $file) {
                    require_once $file;
                }
            }

            $this->loaded = true;
            return true;
        }

        $this->defineWordPressConstants();

        if (!$this->loadWordPressConfig()) {
            return false;
        }

        require_once $this->wpPath . '/wp-load.php';

        $this->loaded = true;
        return true;
    }


    private function defineWordPressConstants(): void
    {
        if (!defined('ABSPATH')) {
            define('ABSPATH', $this->wpPath . '/');
        }

        if (!defined('DISABLE_WP_CRON')) {
            define('DISABLE_WP_CRON', true);
        }

        if (!defined('WP_DEBUG')) {
            define('WP_DEBUG', env('APP_DEBUG', false));
        }
    }

    private function loadWordPressConfig(): bool
    {
        $configPath = $this->wpPath . '/wp-config.php';
        if (file_exists($configPath)) {
            require_once $configPath;
            return true;
        }
        return false;
    }

    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    public function getWordPressPath(): string
    {
        return $this->wpPath;
    }
}
