<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress;

class WordPressConfig
{
    private const KEY_MAPPING = [
        'DB_NAME' => 'DB_DATABASE',
        'DB_USER' => 'DB_USERNAME',
        'DB_PASSWORD' => 'DB_PASSWORD',
        'DB_HOST' => 'DB_HOST',
        'DB_CHARSET' => 'DB_CHARSET',
    ];

    public static function parse(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [];
        }

        $raw = file_get_contents($filePath);
        if ($raw === false || $raw === '') {
            return [];
        }

        $config = [];

        $pattern = '/define\s*\(\s*[\'"](?<key>[A-Z0-9_]+)[\'"]\s*,\s*(?<value>\'[^\']*(?:\\\.[^\']*)*\'|"[^"]*(?:\\\.[^"]*)*"|true|false|[0-9.]+)\s*\)\s*;/i';

        if (preg_match_all($pattern, $raw, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $key = $match['key'];
                $valueRaw = $match['value'];

                $value = self::parseValue($valueRaw);

                if (isset(self::KEY_MAPPING[$key])) {
                    $config[self::KEY_MAPPING[$key]] = $value;
                }

                $config[$key] = $value;
            }
        }

        if (preg_match('/\$table_prefix\s*=\s*[\'"](?<prefix>[a-zA-Z0-9_]+)[\'"]\s*;/', $raw, $m)) {
            $config['WP_TABLE_PREFIX'] = $m['prefix'];
        }

        $dir = dirname($filePath);
        $wpContent = $dir . '/wp-content';
        if (is_dir($wpContent)) {
            $config['WP_CONTENT_DIR'] = realpath($wpContent);
        }

        return $config;
    }

    public static function detect(string $basePath): ?string
    {
        $candidates = [
            $basePath . '/wp-config.php',
            dirname($basePath) . '/wp-config.php',
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    public static function hasContentDir(string $basePath): bool
    {
        $candidates = [
            $basePath . '/wp-content',
            $basePath . '/content',
            dirname($basePath) . '/wp-content',
        ];

        foreach ($candidates as $path) {
            if (is_dir($path)) {
                return true;
            }
        }

        return false;
    }

    private static function parseValue(string $value): mixed
    {
        if (strtolower($value) === 'true') return true;
        if (strtolower($value) === 'false') return false;

        if ((str_starts_with($value, "'") && str_ends_with($value, "'")) ||
            (str_starts_with($value, '"') && str_ends_with($value, '"'))
        ) {
            return substr($value, 1, -1);
        }

        if (is_numeric($value)) return $value;

        return $value;
    }
}
