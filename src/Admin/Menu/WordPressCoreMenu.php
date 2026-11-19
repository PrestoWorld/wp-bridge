<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Admin\Menu;

use PrestoWorld\Contracts\Admin\Menu\MenuContextRepository;

/**
 * Standard builder for the WordPress core admin menu structure.
 *
 * Registers core top-level menus/submenus into the PrestoWorld menu stack
 * (MenuContextRepository) and can export the classic wp-admin section shape
 * consumed by the bridge layout.
 */
final class WordPressCoreMenu
{
    /** @var list<array{id:string,label:string,icon:string,priority:int}> */
    private const SECTIONS = [
        ['id' => 'wp-core', 'label' => 'WordPress', 'icon' => 'dashicons-dashboard', 'priority' => 1],
        ['id' => 'wp-content', 'label' => 'Content', 'icon' => 'dashicons-admin-post', 'priority' => 2],
        ['id' => 'wp-appearance', 'label' => 'Appearance', 'icon' => 'dashicons-admin-appearance', 'priority' => 60],
        ['id' => 'wp-plugins', 'label' => 'Plugins', 'icon' => 'dashicons-admin-plugins', 'priority' => 65],
        ['id' => 'wp-users', 'label' => 'Users', 'icon' => 'dashicons-admin-users', 'priority' => 70],
        ['id' => 'wp-tools', 'label' => 'Tools', 'icon' => 'dashicons-admin-tools', 'priority' => 75],
        ['id' => 'wp-settings', 'label' => 'Settings', 'icon' => 'dashicons-admin-settings', 'priority' => 80],
    ];

    /** @var list<array<string,mixed>> */
    private const TOP_LEVEL = [
        ['id' => 'menu-dashboard', 'screenId' => 'dashboard', 'label' => 'Dashboard', 'url' => '/wp-admin/index.php', 'icon' => 'dashicons-dashboard', 'priority' => 2, 'section' => 'wp-core'],
        ['id' => 'menu-posts', 'screenId' => 'posts', 'label' => 'Posts', 'url' => '/wp-admin/edit.php', 'icon' => 'dashicons-admin-post', 'priority' => 5, 'section' => 'wp-content'],
        ['id' => 'menu-media', 'screenId' => 'upload', 'label' => 'Media', 'url' => '/wp-admin/upload.php', 'icon' => 'dashicons-admin-media', 'priority' => 10, 'section' => 'wp-content'],
        ['id' => 'menu-pages', 'screenId' => 'edit-pages', 'label' => 'Pages', 'url' => '/wp-admin/edit-pages.php', 'icon' => 'dashicons-admin-page', 'priority' => 20, 'section' => 'wp-content'],
        ['id' => 'menu-comments', 'screenId' => 'edit-comments', 'label' => 'Comments', 'url' => '/wp-admin/edit-comments.php', 'icon' => 'dashicons-admin-comments', 'priority' => 25, 'section' => 'wp-content'],
        ['id' => 'menu-appearance', 'screenId' => 'themes', 'label' => 'Appearance', 'url' => '/wp-admin/themes.php', 'icon' => 'dashicons-admin-appearance', 'priority' => 60, 'section' => 'wp-appearance'],
        ['id' => 'menu-plugins', 'screenId' => 'plugins', 'label' => 'Plugins', 'url' => '/wp-admin/plugins.php', 'icon' => 'dashicons-admin-plugins', 'priority' => 65, 'section' => 'wp-plugins'],
        ['id' => 'menu-users', 'screenId' => 'users', 'label' => 'Users', 'url' => '/wp-admin/users.php', 'icon' => 'dashicons-admin-users', 'priority' => 70, 'section' => 'wp-users'],
        ['id' => 'menu-tools', 'screenId' => 'tools', 'label' => 'Tools', 'url' => '/wp-admin/tools.php', 'icon' => 'dashicons-admin-tools', 'priority' => 75, 'section' => 'wp-tools'],
        ['id' => 'menu-settings', 'screenId' => 'settings', 'label' => 'Settings', 'url' => '/wp-admin/options-general.php', 'icon' => 'dashicons-admin-settings', 'priority' => 80, 'section' => 'wp-settings'],
    ];

    /** @var array<string, list<array<string,mixed>>> */
    private const SUBMENUS = [
        'menu-dashboard' => [
            ['id' => 'submenu-dashboard-home', 'screenId' => 'dashboard', 'label' => 'Home', 'url' => '/wp-admin/index.php', 'priority' => 0],
            ['id' => 'submenu-dashboard-updates', 'screenId' => 'update-core', 'label' => 'Updates', 'url' => '/wp-admin/update-core.php', 'priority' => 10],
        ],
        'menu-posts' => [
            ['id' => 'submenu-posts-all', 'screenId' => 'posts', 'label' => 'All Posts', 'url' => '/wp-admin/edit.php', 'priority' => 5],
            ['id' => 'submenu-posts-new', 'screenId' => 'post-new', 'label' => 'Add New Post', 'url' => '/wp-admin/post-new.php', 'priority' => 10],
        ],
        'menu-media' => [
            ['id' => 'submenu-media-library', 'screenId' => 'upload', 'label' => 'Library', 'url' => '/wp-admin/upload.php', 'priority' => 5],
            ['id' => 'submenu-media-new', 'screenId' => 'media-new', 'label' => 'Add New Media File', 'url' => '/wp-admin/media-new.php', 'priority' => 10],
        ],
        'menu-pages' => [
            ['id' => 'submenu-pages-all', 'screenId' => 'edit-pages', 'label' => 'All Pages', 'url' => '/wp-admin/edit-pages.php', 'priority' => 5],
        ],
        'menu-comments' => [
            ['id' => 'submenu-comments-all', 'screenId' => 'edit-comments', 'label' => 'All Comments', 'url' => '/wp-admin/edit-comments.php', 'priority' => 0],
        ],
        'menu-appearance' => [
            ['id' => 'submenu-appearance-themes', 'screenId' => 'themes', 'label' => 'Themes', 'url' => '/wp-admin/themes.php', 'priority' => 5],
            ['id' => 'submenu-appearance-customize', 'screenId' => 'customize', 'label' => 'Customize', 'url' => '/wp-admin/customize.php', 'priority' => 6],
            ['id' => 'submenu-appearance-widgets', 'screenId' => 'widgets', 'label' => 'Widgets', 'url' => '/wp-admin/widgets.php', 'priority' => 7],
            ['id' => 'submenu-appearance-menus', 'screenId' => 'nav-menus', 'label' => 'Menus', 'url' => '/wp-admin/nav-menus.php', 'priority' => 10],
            ['id' => 'submenu-appearance-editor', 'screenId' => 'theme-editor', 'label' => 'Theme File Editor', 'url' => '/wp-admin/theme-editor.php', 'priority' => 20],
        ],
        'menu-plugins' => [
            ['id' => 'submenu-plugins-installed', 'screenId' => 'plugins', 'label' => 'Installed Plugins', 'url' => '/wp-admin/plugins.php', 'priority' => 5],
            ['id' => 'submenu-plugins-add', 'screenId' => 'plugin-install', 'label' => 'Add New Plugin', 'url' => '/wp-admin/plugin-install.php', 'priority' => 10],
            ['id' => 'submenu-plugins-editor', 'screenId' => 'plugin-editor', 'label' => 'Plugin File Editor', 'url' => '/wp-admin/plugin-editor.php', 'priority' => 15],
        ],
        'menu-users' => [
            ['id' => 'submenu-users-all', 'screenId' => 'users', 'label' => 'All Users', 'url' => '/wp-admin/users.php', 'priority' => 5],
            ['id' => 'submenu-users-new', 'screenId' => 'user-new', 'label' => 'Add New User', 'url' => '/wp-admin/user-new.php', 'priority' => 10],
            ['id' => 'submenu-users-profile', 'screenId' => 'profile', 'label' => 'Profile', 'url' => '/wp-admin/profile.php', 'priority' => 15],
        ],
        'menu-tools' => [
            ['id' => 'submenu-tools-available', 'screenId' => 'tools', 'label' => 'Available Tools', 'url' => '/wp-admin/tools.php', 'priority' => 5],
            ['id' => 'submenu-tools-import', 'screenId' => 'import', 'label' => 'Import', 'url' => '/wp-admin/import.php', 'priority' => 10],
            ['id' => 'submenu-tools-export', 'screenId' => 'export', 'label' => 'Export', 'url' => '/wp-admin/export.php', 'priority' => 15],
            ['id' => 'submenu-tools-site-health', 'screenId' => 'site-health', 'label' => 'Site Health', 'url' => '/wp-admin/site-health.php', 'priority' => 20],
        ],
        'menu-settings' => [
            ['id' => 'submenu-settings-general', 'screenId' => 'settings', 'label' => 'General', 'url' => '/wp-admin/options-general.php', 'priority' => 10],
            ['id' => 'submenu-settings-writing', 'screenId' => 'options-writing', 'label' => 'Writing', 'url' => '/wp-admin/options-writing.php', 'priority' => 20],
            ['id' => 'submenu-settings-reading', 'screenId' => 'options-reading', 'label' => 'Reading', 'url' => '/wp-admin/options-reading.php', 'priority' => 30],
            ['id' => 'submenu-settings-discussion', 'screenId' => 'options-discussion', 'label' => 'Discussion', 'url' => '/wp-admin/options-discussion.php', 'priority' => 40],
            ['id' => 'submenu-settings-media', 'screenId' => 'options-media', 'label' => 'Media', 'url' => '/wp-admin/options-media.php', 'priority' => 50],
            ['id' => 'submenu-settings-permalink', 'screenId' => 'options-permalink', 'label' => 'Permalinks', 'url' => '/wp-admin/options-permalink.php', 'priority' => 60],
            ['id' => 'submenu-settings-privacy', 'screenId' => 'options-privacy', 'label' => 'Privacy', 'url' => '/wp-admin/options-privacy.php', 'priority' => 70],
        ],
    ];

    public static function register(MenuContextRepository $menu): void
    {
        if ($menu->has('menu-dashboard')) {
            return;
        }

        foreach (self::SECTIONS as $section) {
            $menu->registerSection($section['id'], $section['label'], $section['icon'], $section['priority']);
        }

        foreach (self::TOP_LEVEL as $top) {
            $menu->registerGroup(
                $top['id'],
                $top['label'],
                $top['icon'],
                null,
                [
                    'section' => $top['section'],
                    'url' => $top['url'],
                    'source' => 'wp-core',
                ],
                $top['priority']
            );

            foreach (self::SUBMENUS[$top['id']] ?? [] as $submenu) {
                $menu->registerItem(
                    $top['id'],
                    (string) ($submenu['label'] ?? ''),
                    (string) ($submenu['url'] ?? ''),
                    $top['icon'],
                    null,
                    $submenu['attributes'] ?? [],
                    (int) ($submenu['priority'] ?? 10),
                    isset($submenu['id']) ? (string) $submenu['id'] : null,
                    isset($submenu['screenId']) ? (string) $submenu['screenId'] : null
                );
            }
        }
    }

    /**
     * Export repository tree as classic wp-admin layout sections.
     *
     * @return list<array<string,mixed>>
     */
    public static function toClassicSections(MenuContextRepository $menu): array
    {
        $sections = [];
        foreach ($menu->getTree() as $section) {
            foreach (($section['groups'] ?? []) as $group) {
                $children = $group['children'] ?? $group['items'] ?? [];
                $items = [];
                foreach ($children as $child) {
                    $items[] = [
                        'id' => $child['id'] ?? $child['screenId'] ?? '',
                        'screenId' => $child['screenId'] ?? $child['id'] ?? '',
                        'label' => $child['label'] ?? $child['title'] ?? '',
                        'icon' => $child['icon'] ?? ($group['icon'] ?? 'dashicons-admin-generic'),
                        'url' => $child['url'] ?? '',
                        'priority' => $child['priority'] ?? 10,
                    ];
                }
                $first = $items[0] ?? [];
                $sections[] = [
                    'id' => $group['id'] ?? ($section['id'] ?? 'section'),
                    'title' => $group['label'] ?? ($section['label'] ?? ''),
                    'priority' => $group['priority'] ?? ($section['priority'] ?? 10),
                    'icon' => $group['icon'] ?? ($section['icon'] ?? 'dashicons-admin-generic'),
                    'screenId' => $first['screenId'] ?? ($group['screenId'] ?? ($group['id'] ?? '')),
                    'items' => $items,
                ];
            }
        }
        usort($sections, static fn(array $a, array $b) => ($a['priority'] ?? 0) <=> ($b['priority'] ?? 0));
        return $sections;
    }

    /**
     * Register core menu then return classic layout sections.
     *
     * @return list<array<string,mixed>>
     */
    public static function buildClassicSections(MenuContextRepository $menu): array
    {
        self::register($menu);
        return self::toClassicSections($menu);
    }
}
