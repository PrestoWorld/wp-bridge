<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Options;

/**
 * Class OptionsManager
 * 
 * High-performance option management for PrestoWorld Super CMS.
 */
class OptionsManager
{
    protected array $allOptions = [];
    protected array $runtimeCache = [];
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

        if (isset($this->runtimeCache[$option])) {
            return $this->runtimeCache[$option];
        }

        // Fetch from DB if not in autoloaded or runtime cache
        $value = $this->fetchFromDb($option);
        if ($value !== false) {
            $this->runtimeCache[$option] = $value;
            return $value;
        }

        return $default;
    }

    /**
     * Update an option
     */
    public function update(string $option, mixed $value, bool $autoload = null): bool
    {
        $this->ensureLoaded();
        
        $serializedValue = is_array($value) || is_object($value) ? serialize($value) : $value;
        
        // Update local state
        if ($autoload === true || (!isset($this->allOptions[$option]) && $autoload === null)) {
            $this->allOptions[$option] = $serializedValue;
        } else {
            $this->runtimeCache[$option] = $value;
        }

        // Persist to DB
        return $this->persistToDb($option, $serializedValue, $autoload);
    }

    /**
     * Delete an option
     */
    public function delete(string $option): bool
    {
        $this->ensureLoaded();
        
        unset($this->allOptions[$option]);
        unset($this->runtimeCache[$option]);

        $db = app('wpdb');
        return (bool)$db->delete('wp_options', ['option_name' => $option]);
    }

    // --- WordPress Compatibility Aliases ---
    
    public function get_option(string $option, mixed $default = false): mixed { return $this->get($option, $default); }
    public function update_option(string $option, mixed $value, bool $autoload = null): bool { return $this->update($option, $value, $autoload); }
    public function add_option(string $option, mixed $value, string $deprecated = '', bool $autoload = true): bool { return $this->update($option, $value, $autoload); }
    public function delete_option(string $option): bool { return $this->delete($option); }
    
    public function get_site_option(string $option, mixed $default = false): mixed { return $this->get($option, $default); }
    public function update_site_option(string $option, mixed $value): bool { return $this->update($option, $value); }

    // --- Transient Support ---

    public function get_transient(string $transient): mixed
    {
        $value = $this->get("_wp_t_{$transient}");
        if ($value === false) return false;

        // Check expiration
        $expiration = $this->get("_wp_t_timeout_{$transient}");
        if ($expiration !== false && time() > (int)$expiration) {
            $this->delete_transient($transient);
            return false;
        }

        return $value;
    }

    public function set_transient(string $transient, mixed $value, int $expiration = 0): bool
    {
        $result = $this->update("_wp_t_{$transient}", $value, false);
        if ($expiration > 0) {
            $this->update("_wp_t_timeout_{$transient}", time() + $expiration, false);
        }
        return $result;
    }

    public function delete_transient(string $transient): bool
    {
        $this->delete("_wp_t_{$transient}");
        $this->delete("_wp_t_timeout_{$transient}");
        return true;
    }

    /**
     * Load all 'autoload' options at once
     */
    protected function ensureLoaded(): void
    {
        if ($this->loaded) return;
        $this->allOptions = $this->warmupAllOptions();
        $this->loaded = true;
    }

    protected function warmupAllOptions(): array
    {
        $db = app('wpdb');
        $results = $db->get_results("SELECT option_name, option_value FROM wp_options WHERE autoload = 'yes' OR autoload = 'on'");
        
        $options = [];
        foreach ($results as $row) {
            $options[$row->option_name] = $row->option_value;
        }

        // Demo Seeds (Commented out to use database values)
        // $options['active_plugins'] = serialize(['legacy-demo/legacy-demo.php']);
        // $options['active_native_plugins'] = serialize(['presto-native-demo']);
        // $options['template'] = 'twentytwenty';
        // $options['native_theme'] = 'tucnguyen';

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
