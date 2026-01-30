<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Sandbox;

/**
 * Class GlobalManager
 * 
 * Provides a managed interface to WordPress globals within the PrestoWorld container.
 * This helps avoid actual $GLOBALS pollution in long-running processes.
 */
class GlobalManager
{
    /**
     * Get a global variable value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $GLOBALS[$key] ?? $default;
    }

    /**
     * Set a global variable value.
     */
    public function set(string $key, mixed $value): void
    {
        $GLOBALS[$key] = $value;
    }

    /**
     * Check if a global variable is set.
     */
    public function has(string $key): bool
    {
        return isset($GLOBALS[$key]);
    }

    /**
     * Magic access for $global->wpdb style.
     */
    public function __get(string $key): mixed
    {
        return $this->get($key);
    }
}
