<?php

use Prestoworld\Bridge\WordPress\Exceptions\WordPressCompatibilityException;

if (!function_exists('is_admin')) {
    function is_admin() {
        return defined('WP_ADMIN') && WP_ADMIN;
    }
}

if (!function_exists('is_404')) {
    function is_404() {
        return http_response_code() === 404;
    }
}

if (!function_exists('wp_mail')) {
    function wp_mail() {
        if (env('WP_BRIDGE_STRICT', true)) {
            throw WordPressCompatibilityException::notImplemented('wp_mail');
        }
        return false;
    }
}

if (!function_exists('get_current_screen')) {
    function get_current_screen() {
        return (object)[
            'id' => $GLOBALS['__presto_admin_context']['screen'] ?? 'dashboard',
            'base' => $GLOBALS['__presto_admin_context']['screen'] ?? 'dashboard',
        ];
    }
}
