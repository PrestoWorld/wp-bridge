<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Sandbox;

use PrestoWorld\Hooks\HookManager;
use PrestoWorld\Admin\AdminManager;

/**
 * Class IsolationSandbox
 * 
 * Implements a "Soft Sandbox" for WordPress code.
 * Instead of spinning up a separate VM/Container, it uses PHP variable scoping
 * and temporary global overriding to isolate WP execution effects.
 */
class IsolationSandbox implements SandboxInterface
{
    protected array $capturedHooks = [];
    protected array $originalGlobals = [];
    
    public function __construct(
        protected HookManager $prestoHooks,
        protected AdminManager $prestoAdmin
    ) {}

    public function run(callable $callback, array $context = [])
    {
        $this->backupGlobals();
        
        // Inject Context
        foreach ($context as $key => $value) {
            $GLOBALS[$key] = $value;
        }

        // Start Hook Capture Mode (Intercept add_action/add_filter)
        $GLOBALS['__presto_sandbox_capture'] = true;
        
        try {
            // Execute the WordPress Code
            $result = $callback();
        } finally {
            // Cleanup: Stop Capture & Restore Globals
            unset($GLOBALS['__presto_sandbox_capture']);
            $this->restoreGlobals();
        }

        return $result;
    }

    public function captureHooks(): array
    {
        // In a real scenario, our 'add_action' helper would populate this
        // when '__presto_sandbox_capture' is true.
        // For now, we interact with the global registry state.
        return $GLOBALS['__presto_captured_hooks'] ?? [];
    }

    public function resolve(): void
    {
        $hooks = $this->captureHooks();
        
        foreach ($hooks as $hook) {
            // Translate WP Hook -> Presto Hook
            if ($hook['tag'] === 'admin_menu') {
                call_user_func($hook['callback']); 
                continue;
            }

            if ($hook['type'] === 'filter') {
                $this->prestoHooks->addFilter($hook['tag'], $hook['callback'], $hook['priority']);
            } else {
                $this->prestoHooks->addAction($hook['tag'], $hook['callback'], $hook['priority']);
            }
        }
        
        // Clear capture buffer
        $GLOBALS['__presto_captured_hooks'] = [];
    }
    
    protected function backupGlobals(): void
    {
        // Backup critical WP globals only to save memory
        // We use a predefined safe-list to avoid copying the entire world.
        $keys = ['wp_filter', 'wp_actions', 'wp_current_filter', 'current_user', 'wpdb', 'post', 'wp_query'];
        foreach ($keys as $key) {
            if (isset($GLOBALS[$key])) {
                // IMPORTANT: We clone objects if possible to avoid shared mutable state bleeding
                $val = $GLOBALS[$key];
                if (is_object($val)) {
                     // Shallow clone usually enough for top-level restoration, 
                     // but ideal is deep clone or just keeping the reference to restore BACK.
                     $this->originalGlobals[$key] = $val;
                } else {
                    $this->originalGlobals[$key] = $val;
                }
            } else {
                // Record that it was unset, so we can unset it later
                $this->originalGlobals[$key] = '__PRESTO_UNSET__';
            }
        }
    }

    protected function restoreGlobals(): void
    {
        // 1. Restore original values
        foreach ($this->originalGlobals as $key => $value) {
            if ($value === '__PRESTO_UNSET__') {
                unset($GLOBALS[$key]);
            } else {
                $GLOBALS[$key] = $value;
            }
        }

        // 2. Identify and cleanup leaked Globals created during execution
        // This is expensive (scan all globals) but necessary for true isolation?
        // A better approach is to track what we injected via $context and unset those.
        // And maybe a known list of "garbage" WP creates.

        // 3. Hard Cleanup
        $this->originalGlobals = [];
        $this->capturedHooks = [];
        
        // Force garbage collection cycle if heavy memory usage detected?
        // if (memory_get_usage() > 128 * 1024 * 1024) gc_collect_cycles();
    }
}
