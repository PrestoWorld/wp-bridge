<?php

use PrestoWorld\Bridge\WordPress\Exceptions\WordPressCompatibilityException;

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

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('number_format_i18n')) {
    function number_format_i18n($number, $decimals = 0) {
        return number_format((float)$number, $decimals);
    }
}

if (!function_exists('add_query_arg')) {
    function add_query_arg(...$args) {
        // Simplified implementation
        if (is_array($args[0])) {
            $params = $args[0];
            $url = $args[1] ?? $_SERVER['REQUEST_URI'] ?? '';
        } else {
             $params = [$args[0] => $args[1]];
             $url = $args[2] ?? $_SERVER['REQUEST_URI'] ?? '';
        }
        
        $parts = parse_url($url);
        $query = [];
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
        }
        $query = array_merge($query, $params);
        
        $query_str = http_build_query($query);
        return ($parts['path'] ?? '') . '?' . $query_str;
    }
}

if (!function_exists('is_user_logged_in')) {
    function is_user_logged_in() {
        if (!app()->has(\Witals\Framework\Contracts\Auth\AuthContextInterface::class)) {
            return false;
        }
        $auth = app(\Witals\Framework\Contracts\Auth\AuthContextInterface::class);
        return $auth->getToken() !== null;
    }
}

if (!function_exists('auth_redirect')) {
    function auth_redirect() {
        if (!is_user_logged_in()) {
            $login_url = '/wp-login.php?redirect_to=' . urlencode($_SERVER['REQUEST_URI'] ?? '/wp-admin/');
            header('Location: ' . $login_url);
            exit;
        }
    }
}

// WordPress standard globals
$GLOBALS['wp_version'] = '6.4.1';
$GLOBALS['wp_db_version'] = 56657;
$GLOBALS['tinymce_version'] = '49110-20200713';
$GLOBALS['wp_local_package'] = 'en_US';

