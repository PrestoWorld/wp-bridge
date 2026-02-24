<?php

declare(strict_types=1);

if (!function_exists('__')) {
    /**
     * Retrieve the translation of $text.
     */
    function __($text, $domain = 'default') {
        if (!app()->has('translator')) {
            return $text;
        }
        return app()->translator()->get((string)$text);
    }
}

if (!function_exists('_e')) {
    /**
     * Display translated text.
     */
    function _e($text, $domain = 'default') {
        echo __($text, $domain);
    }
}

if (!function_exists('_x')) {
    /**
     * Retrieve translated string with gettext context.
     */
    function _x($text, $context, $domain = 'default') {
        return __($text, $domain);
    }
}

if (!function_exists('_ex')) {
    /**
     * Display translated string with gettext context.
     */
    function _ex($text, $context, $domain = 'default') {
        echo _x($text, $context, $domain);
    }
}

if (!function_exists('_n')) {
    /**
     * Retrieve the plural or single form based on the amount.
     */
    function _n($single, $plural, $number, $domain = 'default') {
        return $number == 1 ? __($single, $domain) : __($plural, $domain);
    }
}

if (!function_exists('get_locale')) {
    /**
     * Gets the current locale of the application.
     */
    function get_locale() {
        if (!app()->has('translator')) {
            return 'en_US';
        }
        $locale = app()->translator()->getLocale();
        // Convert to WordPress format (e.g. vi -> vi_VN)
        if ($locale === 'vi') return 'vi_VN';
        if ($locale === 'en') return 'en_US';
        return $locale;
    }
}
