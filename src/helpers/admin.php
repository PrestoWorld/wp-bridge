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
