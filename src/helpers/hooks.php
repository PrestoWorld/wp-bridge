<?php

use PrestoWorld\Hooks\HookManager;
use PrestoWorld\Contracts\Hooks\HookStateType;

if (!function_exists('add_filter')) {
    function add_filter($hook_name, $callback, $priority = 10, $accepted_args = 1, HookStateType $stateType = HookStateType::SCOPED) {
        $hooks = app(HookManager::class);
        $hooks->addFilter($hook_name, $callback, $priority, $stateType);
        return true;
    }
}

if (!function_exists('add_action')) {
    function add_action($hook_name, $callback, $priority = 10, $accepted_args = 1, HookStateType $stateType = HookStateType::SCOPED) {
        $hooks = app(HookManager::class);
        $hooks->addAction($hook_name, $callback, $priority, $stateType);
        return true;
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($hook_name, $value, ...$args) {
        $hooks = app(HookManager::class);
        return $hooks->applyFilters($hook_name, $value, ...$args);
    }
}

if (!function_exists('do_action')) {
    function do_action($hook_name, ...$args) {
        $hooks = app(HookManager::class);
        $hooks->doAction($hook_name, ...$args);
    }
}

if (!function_exists('do_action_ref_array')) {
    /**
     * Execute functions hooked on a specific action hook, specifying arguments in an array.
     */
    function do_action_ref_array($hook_name, $args) {
        $hooks = app(HookManager::class);
        $hooks->doAction($hook_name, ...$args);
    }
}

if (!function_exists('remove_filter')) {
    function remove_filter($hook_name, $callback, $priority = 10) {
        $hooks = app(HookManager::class);
        $hooks->removeFilter($hook_name, $callback, $priority);
        return true; 
    }
}

if (!function_exists('remove_action')) {
    function remove_action($hook_name, $callback, $priority = 10) {
        $hooks = app(HookManager::class);
        $hooks->removeAction($hook_name, $callback, $priority);
        return true;
    }
}

if (!function_exists('remove_all_filters')) {
    function remove_all_filters($hook_name, $priority = false) {
        $hooks = app(HookManager::class);
        $hooks->removeAllFilters($hook_name, $priority);
        return true;
    }
}

if (!function_exists('remove_all_actions')) {
    function remove_all_actions($hook_name, $priority = false) {
        $hooks = app(HookManager::class);
        $hooks->removeAllActions($hook_name, $priority);
        return true;
    }
}

if (!function_exists('did_action')) {
    function did_action($tag) {
        $hooks = app(HookManager::class);
        return $hooks->didAction($tag);
    }
}

if (!function_exists('has_action')) {
    function has_action($tag, $function_to_check = false) {
        $hooks = app(HookManager::class);
        return $hooks->hasAction($tag, $function_to_check);
    }
}
