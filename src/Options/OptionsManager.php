<?php

declare(strict_types=1);

namespace Prestoworld\Bridge\WordPress\Options;

use Illuminate\Support\Facades\Cache;

/**
 * Class OptionsManager
 * 
 * High-performance option management for PrestoWorld Super CMS.
 * Features:
 * - Redis-first storage for 'alloptions' (autoload)
 * - Intelligent caching for individual options
 * - Zero SQL for alloptions lookup
 */
class OptionsManager
{
    protected array $allOptions = [];
    protected bool $loaded = false;
    protected string $cacheKey = 'wp_alloptions';

    /**
     * Get an option
     */
    public function get(string $option, mixed $default = false): mixed
    {
        $this->ensureLoaded();

        if (isset($this->allOptions[$option])) {
            return $this->maybeUnserialize($this->allOptions[$option]);
        }

        // Individual lookup for non-autoload options
        return Cache::remember("wp_opt_{$option}", 3600, function () use ($option, $default) {
            // Fallback to DB proxy if not in cache
            return $this->fetchFromDb($option) ?: $default;
        });
    }

    /**
     * Update an option
     */
    public function update(string $option, mixed $value, bool $autoload = null): bool
    {
        $this->ensureLoaded();
        
        $serializedValue = is_array($value) || is_object($value) ? serialize($value) : $value;
        $this->allOptions[$option] = $serializedValue;

        // Update Cache/Redis
        Cache::forever($this->cacheKey, $this->allOptions);
        Cache::put("wp_opt_{$option}", $value, 3600);

        // Async: Persist to DB in background (using dispatcher if available)
        // For now, write sync
        return $this->persistToDb($option, $serializedValue, $autoload);
    }

    /**
     * Load all 'autoload' options at once
     */
    protected function ensureLoaded(): void
    {
        if ($this->loaded) return;

        $this->allOptions = Cache::get($this->cacheKey, function() {
            return $this->warmupAllOptions();
        });

        $this->loaded = true;
    }

    protected function warmupAllOptions(): array
    {
        // One-time query to fetch all autoload options
        // SELECT option_name, option_value FROM wp_options WHERE autoload = 'yes'
        $db = app('wpdb');
        $results = $db->get_results("SELECT option_name, option_value FROM wp_options WHERE autoload = 'yes' OR autoload = 'on'");
        
        $options = [];
        foreach ($results as $row) {
            $options[$row->option_name] = $row->option_value;
        }

        return $options;
    }

    protected function maybeUnserialize(mixed $value): mixed
    {
        if (is_string($value) && (strpos($value, 'a:') === 0 || strpos($value, 'O:') === 0)) {
            return @unserialize($value);
        }
        return $value;
    }

    protected function fetchFromDb(string $option): mixed
    {
        $db = app('wpdb');
        $value = $db->get_var($db->prepare("SELECT option_value FROM wp_options WHERE option_name = %s LIMIT 1", $option));
        return $this->maybeUnserialize($value);
    }

    protected function persistToDb(string $option, mixed $value, ?bool $autoload): bool
    {
        $db = app('wpdb');
        $autoloadStr = $autoload === null ? 'yes' : ($autoload ? 'yes' : 'no');
        
        $exists = $db->get_var($db->prepare("SELECT 1 FROM wp_options WHERE option_name = %s", $option));
        
        if ($exists) {
            return (bool)$db->update('wp_options', ['option_value' => $value], ['option_name' => $option]);
        }
        
        return (bool)$db->insert('wp_options', [
            'option_name' => $option,
            'option_value' => $value,
            'autoload' => $autoloadStr
        ]);
    }
}
