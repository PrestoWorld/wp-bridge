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
    }
}
