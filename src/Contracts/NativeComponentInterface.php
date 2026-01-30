<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Contracts;

use Witals\Framework\Http\Response;

/**
 * Interface NativeComponentInterface
 * 
 * Standard interface for PrestoWorld Native Plugins and Themes.
 * Optimized for RoadRunner/Swoole to avoid direct output.
 */
interface NativeComponentInterface
{
    /**
     * Boot the component (Service registration, Hooks registration)
     */
    public function boot(): void;

    /**
     * Handle a request and return a clean Response object
     * instead of echo-ing.
     */
    public function handle(string $action, array $params = []): Response;
}
