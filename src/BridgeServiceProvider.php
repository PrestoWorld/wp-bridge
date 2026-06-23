<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress;

use Witals\Framework\Support\ServiceProvider;
use App\Http\Routing\Contracts\RouterInterface;

class BridgeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $basePath = $this->app->basePath();

        $wpConfigPath = WordPressConfig::detect($basePath);

        if ($wpConfigPath !== null) {
            $wpConfig = WordPressConfig::parse($wpConfigPath);

            foreach ($wpConfig as $key => $value) {
                if (is_string($value) || is_bool($value) || is_int($value)) {
                    putenv("{$key}={$value}");
                    $_ENV[$key] = $value;
                }
            }

            $this->app->instance(WordPressConfig::class, $wpConfig);

            // Switch to MySQL when WordPress config is present
            putenv('DB_CONNECTION=mysql');
            $_ENV['DB_CONNECTION'] = 'mysql';

            $prefix = $wpConfig['WP_TABLE_PREFIX'] ?? 'wp_';
            $this->app->instance('wp-bridge.table_prefix', $prefix);
            $this->app->instance('wp-bridge.wordpress_detected', true);
        }
    }

    public function boot(): void
    {
        $router = $this->app->make(RouterInterface::class);

        if ($this->hasWordPress()) {
            $router->loadRoutesFrom(__DIR__ . '/../routes/wp-admin.php');
            $this->configureForWordPress();
        }
    }

    protected function hasWordPress(): bool
    {
        return $this->app->has('wp-bridge.wordpress_detected')
            && $this->app->make('wp-bridge.wordpress_detected') === true;
    }

    protected function configureForWordPress(): void
    {
        $prefix = $this->app->make('wp-bridge.table_prefix');

        putenv("PW_TABLE_PREFIX={$prefix}");
        $_ENV['PW_TABLE_PREFIX'] = $prefix;

        $wpBridgeModels = $this->app->basePath('vendor/prestoworld/wp-bridge/src/Models');
        if (is_dir($wpBridgeModels)) {
            $this->app->instance('db.entity_paths', [$wpBridgeModels]);
        }

        $this->configureThemePaths();
    }

    protected function configureThemePaths(): void
    {
        $contentDir = null;
        $config = $this->app->has(WordPressConfig::class)
            ? $this->app->make(WordPressConfig::class)
            : null;

        if ($config !== null && isset($config['WP_CONTENT_DIR'])) {
            $contentDir = $config['WP_CONTENT_DIR'];
        } else {
            $candidates = [
                $this->app->basePath('wp-content'),
                $this->app->basePath('content'),
                dirname($this->app->basePath()) . '/wp-content',
            ];
            foreach ($candidates as $path) {
                if (is_dir($path)) {
                    $contentDir = realpath($path);
                    break;
                }
            }
        }

        if ($contentDir === null) {
            return;
        }

        putenv("PW_CONTENT_DIR={$contentDir}");
        $_ENV['PW_CONTENT_DIR'] = $contentDir;

        $contentUrl = '/' . basename($contentDir);
        putenv("PW_CONTENT_URL={$contentUrl}");
        $_ENV['PW_CONTENT_URL'] = $contentUrl;

        $themesDir = $contentDir . '/themes';
        if (!is_dir($themesDir)) {
            return;
        }

        $theme = getenv('PW_THEME_ACTIVE') ?: $this->detectActiveTheme($themesDir);
        if ($theme === null) {
            return;
        }

        $themeDir = $themesDir . '/' . $theme;
        if (!is_dir($themeDir)) {
            return;
        }

        $themeDir = realpath($themeDir);

        putenv("PW_THEME_ACTIVE={$theme}");
        $_ENV['PW_THEME_ACTIVE'] = $theme;

        putenv("PW_THEME_DIR={$themeDir}");
        $_ENV['PW_THEME_DIR'] = $themeDir;
    }

    protected function detectActiveTheme(string $themesDir): ?string
    {
        $entries = scandir($themesDir);
        if ($entries === false) {
            return null;
        }

        $themes = array_values(
            array_filter($entries, fn(string $d): bool => $d[0] !== '.' && is_dir("{$themesDir}/{$d}"))
        );

        if (empty($themes)) {
            return null;
        }

        $preferred = ['twentytwentyfive', 'twentytwentyfour', 'twentytwentythree'];
        foreach ($preferred as $name) {
            if (in_array($name, $themes, true)) {
                return $name;
            }
        }

        return $themes[0];
    }
}
