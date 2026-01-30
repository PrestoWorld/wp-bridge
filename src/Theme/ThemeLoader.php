<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Theme;

use Witals\Framework\Application;
use PrestoWorld\Theme\ThemeManager;

/**
 * Theme Loader for Zero Migration
 * 
 * Automatically detects and loads the active WordPress theme from the database
 * if traditional WordPress structures are present.
 */
class ThemeLoader
{
    protected Application $app;
    protected ThemeManager $themeManager;

    public function __construct(Application $app, ThemeManager $themeManager)
    {
        $this->app = $app;
        $this->themeManager = $themeManager;
    }

    /**
     * Detect and active the theme corresponding to WordPress's configuration
     */
    public function load(): void
    {
        // Check priority: If THEME_ACTIVE is set in .env, respect it as highest priority.
        if (env('THEME_ACTIVE')) {
            return;
        }

        $wpPath = $this->app->basePath('public');
        $wpContentPath = $wpPath . '/wp-content';
        $wpConfigPath = $wpPath . '/wp-config.php';
        $wpThemesPath = $wpContentPath . '/themes';

        // Check if mandatory WordPress paths exist
        if (!file_exists($wpContentPath) || !file_exists($wpConfigPath) || !is_dir($wpThemesPath)) {
            return;
        }

        $themeSlug = $this->getActiveThemeFromDatabase($wpConfigPath);
        
        if ($themeSlug) {
            // Trigger discovery to ensure the theme is found if not already
            $this->themeManager->discover();
            
            // Set the active theme to match WordPress
            $this->themeManager->setActiveTheme($themeSlug);
        }
    }

    /**
     * Query the database for the active theme (template option)
     */
    protected function getActiveThemeFromDatabase(string $wpConfigPath): ?string
    {
        $dbHost = env('DB_HOST');
        $dbName = env('DB_DATABASE');
        $dbUser = env('DB_USERNAME');

        if (!$dbHost || !$dbName || !$dbUser) {
            return null;
        }

        try {
            $dbPass = env('DB_PASSWORD', '');
            $dbPort = env('DB_PORT', 3306);
            $charset = env('DB_CHARSET', 'utf8mb4');
            $prefix = env('WP_TABLE_PREFIX', 'wp_');

            $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset={$charset}";
            $pdo = new \PDO($dsn, $dbUser, $dbPass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);

            $tableName = $prefix . 'options';
            $sql = "SELECT option_value FROM `{$tableName}` WHERE option_name = 'template' LIMIT 1";

            $start = microtime(true);
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $duration = microtime(true) - $start;

            // Log to QueryInterceptor if available
            if ($this->app->has(\App\Foundation\Database\QueryInterceptor::class)) {
                $this->app->make(\App\Foundation\Database\QueryInterceptor::class)->log('info', $sql, ['elapsed' => $duration]);
            }
            
            $result = $stmt->fetch();
            return $result ? (string) $result['option_value'] : null;
        } catch (\PDOException $e) {
            return null;
        }
    }
}
