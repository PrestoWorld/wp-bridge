<?php

declare(strict_types=1);

use PrestoWorld\Admin\UI\SkinManager;
use PrestoWorld\Bridge\WordPress\Admin\WP_List_Table;

if (!function_exists('add_menu_page')) {
    function add_menu_page($page_title, $menu_title, $capability, $menu_slug, $callback = '', $icon_url = '', $position = null) {
        // Implementation that registers this to Presto's admin menu system
        // For now, it's a placeholder to show the API
    }
}

/**
 * Example usage of a UI-agnostic WP_List_Table
 */
function render_wp_list_table(WP_List_Table $table, SkinManager $skinManager) {
    $skin = $skinManager->getActiveSkin();
    if ($skin) {
        $table->set_skin($skin);
        $table->prepare_items();
        $table->display();
    }
}
