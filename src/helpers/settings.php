<?php

if (!function_exists('register_setting')) {
    function register_setting($option_group, $option_name, $args = []) {
        $registry = app(\PrestoWorld\Bridge\WordPress\Settings\SettingsRegistry::class);
        $registry->registerSetting($option_group, $option_name, $args);
    }
}

if (!function_exists('add_settings_section')) {
    function add_settings_section($id, $title, $callback, $page) {
        $registry = app(\PrestoWorld\Bridge\WordPress\Settings\SettingsRegistry::class);
        $registry->addSection($id, $title, $callback, $page);
    }
}

if (!function_exists('add_settings_field')) {
    function add_settings_field($id, $title, $callback, $page, $section = 'default', $args = []) {
        $registry = app(\PrestoWorld\Bridge\WordPress\Settings\SettingsRegistry::class);
        $registry->addField($id, $title, $callback, $page, $section, $args);
    }
}
