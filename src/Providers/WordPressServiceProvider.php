<?php

declare(strict_types=1);

namespace Prestoworld\Bridge\WordPress\Providers;

use App\Support\ServiceProvider;
use Prestoworld\Bridge\WordPress\WordPressLoader;
use Prestoworld\Bridge\WordPress\WordPressBridge;
use Prestoworld\Bridge\WordPress\Theme\ThemeLoader;
use Prestoworld\Bridge\WordPress\Bootstrap\WordPressConceptBootstrap;

class WordPressServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Load WordPress Helpers
        $helpers = __DIR__ . '/../helpers.php';
        if (file_exists($helpers)) {
            require_once $helpers;
        }

        // Register WordPress Loader
        $this->singleton(WordPressLoader::class, function ($app) {
            return new WordPressLoader($app);
        });

        // Register Settings Registry
        $this->singleton(\Prestoworld\Bridge\WordPress\Settings\SettingsRegistry::class, function ($app) {
            return new \Prestoworld\Bridge\WordPress\Settings\SettingsRegistry();
        });
        
        // Register WordPress Bridge
        $this->singleton(WordPressBridge::class, function ($app) {
            return new WordPressBridge(
                $app,
                $app->make(WordPressLoader::class)
            );
        });

        // Register WordPress Concept Bootstrapper
        $this->app->addBootstrapper(WordPressConceptBootstrap::class);

        // Register Admin Bootstrapper (Not auto-booted, called by Driver)
        $this->singleton(\Prestoworld\Bridge\WordPress\Bootstrap\WordPressAdminBootstrap::class, function ($app) {
            return new \Prestoworld\Bridge\WordPress\Bootstrap\WordPressAdminBootstrap();
        });

        // Register Theme Loader for Zero Migration
        $this->singleton(ThemeLoader::class, function ($app) {
            return new ThemeLoader(
                $app,
                $app->make(\PrestoWorld\Theme\ThemeManager::class)
            );
        });

        // Register WordPress Dispatcher for Native Router Fallback
        $this->singleton(\Prestoworld\Bridge\WordPress\Routing\WordPressDispatcher::class, function ($app) {
            return new \Prestoworld\Bridge\WordPress\Routing\WordPressDispatcher($app);
        });
    }

    public function boot(): void
    {
        $themeManager = $this->app->make(\PrestoWorld\Theme\ThemeManager::class);

        // Register WordPress theme discovery path
        $themeManager->addDiscoveryPath($this->app->basePath('public/wp-content/themes'));

        // Register WordPress-specific theme engines (Simulation Mode)
        $themeManager->registerEngine(
            \PrestoWorld\Theme\ThemeType::GUTENBERG->value,
            \Prestoworld\Bridge\WordPress\Theme\Engines\GutenbergEngine::class
        );

        $themeManager->registerEngine(
            \PrestoWorld\Theme\ThemeType::LEGACY->value,
            \Prestoworld\Bridge\WordPress\Theme\Engines\LegacyEngine::class
        );
        
        // Note: WordPressLoader->load() is NOT called. 
        // We are simulating WP behavior natively.

        // Register WordPress Admin Driver if AdminManager is available
        if ($this->app->has(\PrestoWorld\Admin\AdminManager::class)) {
            $this->app->make(\PrestoWorld\Admin\AdminManager::class)->registerDriver(
                'wordpress', 
                \Prestoworld\Bridge\WordPress\Dashboard\WordPressDashboardDriver::class
            );
        }
    }
}
