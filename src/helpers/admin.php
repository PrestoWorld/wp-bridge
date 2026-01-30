<?php

if (!function_exists('add_menu_page')) {
    function add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function = '', $icon_url = '', $position = null) {
        $repo = app(\PrestoWorld\Admin\MenuRepository::class);
        $repo->addMenu(new \PrestoWorld\Admin\Menu(
            $page_title,
            $menu_title,
            $capability,
            $menu_slug,
            $function,
            $icon_url,
            $position
        ));
        return "toplevel_page_{$menu_slug}";
    }
}

if (!function_exists('add_submenu_page')) {
    function add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function = '', $position = null) {
        $repo = app(\PrestoWorld\Admin\MenuRepository::class);
        $repo->addSubMenu(new \PrestoWorld\Admin\SubMenu(
            $parent_slug,
            $page_title,
            $menu_title,
            $capability,
            $menu_slug,
            $function,
            $position
        ));
        return "{$parent_slug}_page_{$menu_slug}";
    }
}

if (!function_exists('wp_add_dashboard_widget')) {
    function wp_add_dashboard_widget($widget_id, $widget_name, $callback, $control_callback = null, $callback_args = null) {
        $repo = app(\PrestoWorld\Admin\DashboardWidgetRepository::class);
        $repo->addWidget(new \PrestoWorld\Admin\DashboardWidget(
            $widget_id,
            $widget_name,
            $callback,
            $control_callback,
            $callback_args ?? []
        ));
    }
}

if (!function_exists('submit_button')) {
    function submit_button($text = null, $type = 'primary', $name = 'submit', $wrap = true, $other_attributes = null) {
        // Convert array attributes to string
        $attrs = '';
        if (is_array($other_attributes)) {
            foreach ($other_attributes as $key => $value) {
                $attrs .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
            }
        } elseif (is_string($other_attributes)) {
            $attrs = ' ' . $other_attributes;
        }
        
        $button = '<input type="' . ($type === 'primary' ? 'submit' : 'button') . '" name="' . esc_attr($name) . '" id="' . esc_attr($name) . '" class="button button-' . esc_attr($type) . '" value="' . esc_attr($text) . '"' . $attrs . ' />';
        if ($wrap) {
            echo '<p class="submit">' . $button . '</p>';
        } else {
            echo $button;
        }
    }
}

if (!function_exists('wp_extract_nonce')) {
    function wp_extract_nonce($nonce) {
        return $nonce; // Dummy
    }
}

if (!function_exists('check_admin_referer')) {
    function check_admin_referer($action = -1, $query_arg = '_wpnonce') {
        return true; // Bypass
    }
}

if (!function_exists('_admin_search_query')) {
    function _admin_search_query() {
        echo esc_attr($_REQUEST['s'] ?? '');
    }
}

if (!function_exists('get_hidden_columns')) {
    function get_hidden_columns($screen) {
        return [];
    }
}

// Bootstrap WP_List_Table alias
if (!class_exists('WP_List_Table')) {
    $namespacedClass = \PrestoWorld\Bridge\WordPress\Admin\WP_List_Table::class;
    // Trigger autoloader
    if (class_exists($namespacedClass)) {
        class_alias($namespacedClass, 'WP_List_Table');
    }
}
