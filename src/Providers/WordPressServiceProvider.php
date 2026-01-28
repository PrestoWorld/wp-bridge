<?php

declare(strict_types=1);

namespace Prestoworld\Bridge\WordPress\Providers;

use App\Support\ServiceProvider;
use Prestoworld\Bridge\WordPress\WordPressLoader;
use Prestoworld\Bridge\WordPress\WordPressBridge;

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
        
        // Register WordPress Bridge
        $this->singleton(WordPressBridge::class, function ($app) {
            return new WordPressBridge(
                $app,
                $app->make(WordPressLoader::class)
            );
        });
    }

    public function boot(): void
    {
        $themeManager = $this->app->make(\PrestoWorld\Theme\ThemeManager::class);

        // Register WordPress-specific theme engines
        $themeManager->registerEngine(
            \PrestoWorld\Theme\ThemeType::GUTENBERG->value,
            \Prestoworld\Bridge\WordPress\Theme\Engines\GutenbergEngine::class
        );

        $themeManager->registerEngine(
            \PrestoWorld\Theme\ThemeType::LEGACY->value,
            \Prestoworld\Bridge\WordPress\Theme\Engines\LegacyEngine::class
        );
    }
}
