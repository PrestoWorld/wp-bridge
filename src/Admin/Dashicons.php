<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Admin;

/**
 * WordPress Dashicons helper.
 *
 * Renders dashicons using the dashicons font (same as WordPress core).
 * Maps Lucide-style icon names to dashicons classes.
 */
class Dashicons
{
    /**
     * Map Lucide-style icon names to dashicons CSS classes.
     */
    public const ICON_MAP = [
        // Navigation / structure
        'LayoutDashboard'  => 'dashicons-dashboard',
        'Dashboard'        => 'dashicons-dashboard',
        'Home'             => 'dashicons-admin-home',
        'FileText'         => 'dashicons-admin-post',
        'File'             => 'dashicons-media-default',
        'FilePage'         => 'dashicons-admin-page',
        'Puzzle'           => 'dashicons-admin-plugins',
        'Settings'         => 'dashicons-admin-settings',
        'Globe'            => 'dashicons-admin-site',
        'Bell'             => 'dashicons-bell',
        'Plus'             => 'dashicons-plus-alt',
        'Circle'           => 'dashicons-marker',
        'Blocks'           => 'dashicons-admin-plugins',
        'MessageSquare'    => 'dashicons-admin-comments',
        'Wrench'           => 'dashicons-admin-tools',
        'Sparkles'         => 'dashicons-star-filled',
        'RefreshCw'        => 'dashicons-update',
        'ShieldAlert'      => 'dashicons-shield',
        'Activity'         => 'dashicons-chart-line',
        'Menu'             => 'dashicons-menu',
        'X'                => 'dashicons-no',
        'Check'            => 'dashicons-yes',
        'Search'           => 'dashicons-search',
        'User'             => 'dashicons-admin-users',
        'BookOpen'         => 'dashicons-book',

        // Media
        'Image'            => 'dashicons-format-image',
        'Camera'           => 'dashicons-camera',
        'Video'            => 'dashicons-video-alt3',
        'Music'            => 'dashicons-format-audio',
        'Upload'           => 'dashicons-upload',
        'Download'         => 'dashicons-download',

        // Content
        'Edit'             => 'dashicons-edit',
        'Trash'            => 'dashicons-trash',
        'Copy'             => 'dashicons-admin-page',
        'Link'             => 'dashicons-admin-links',
        'Calendar'         => 'dashicons-calendar',
        'Clock'            => 'dashicons-clock',
        'Tag'              => 'dashicons-tag',
        'Tags'             => 'dashicons-tag',
        'Category'         => 'dashicons-category',

        // Users
        'Users'            => 'dashicons-admin-users',
        'UserAdd'          => 'dashicons-admin-users',
        'Profile'          => 'dashicons-admin-users',

        // Tools
        'Tools'            => 'dashicons-admin-tools',
        'Admin'            => 'dashicons-admin-generic',
        'Generic'          => 'dashicons-admin-generic',
        'Lock'             => 'dashicons-lock',
        'Unlock'           => 'dashicons-unlock',
        'Star'             => 'dashicons-star-filled',
        'StarEmpty'        => 'dashicons-star-empty',
        'Heart'            => 'dashicons-heart',
        'Info'             => 'dashicons-info',
        'Warning'          => 'dashicons-warning',
        'Error'            => 'dashicons-no-alt',
        'Success'          => 'dashicons-yes-alt',

        // Appearance
        'Appearance'       => 'dashicons-admin-appearance',
        'Customizer'       => 'dashicons-admin-customizer',
        'Themes'           => 'dashicons-admin-appearance',
        'Widgets'          => 'dashicons-screenoptions',
        'Menus'            => 'dashicons-admin-appearance',

        // Network / multisite
        'Network'          => 'dashicons-admin-network',
        'Multisite'        => 'dashicons-admin-multisite',
        'Site'             => 'dashicons-admin-site',

        // Comments
        'Comments'         => 'dashicons-admin-comments',
        'Comment'          => 'dashicons-admin-comments',

        // Posts
        'Posts'            => 'dashicons-admin-post',
        'Post'             => 'dashicons-admin-post',
        'Pages'            => 'dashicons-admin-page',
        'Page'             => 'dashicons-admin-page',

        // Plugins
        'Plugins'          => 'dashicons-admin-plugins',
        'Plugin'           => 'dashicons-admin-plugins',

        // Media / upload
        'Media'            => 'dashicons-admin-media',
        'Library'          => 'dashicons-admin-media',

        // Analytics
        'Chart'            => 'dashicons-chart-line',
        'Charts'           => 'dashicons-chart-bar',
        'Analytics'        => 'dashicons-chart-area',
        'Stats'            => 'dashicons-chart-bar',

        // Misc
        'ArrowUp'          => 'dashicons-arrow-up',
        'ArrowDown'        => 'dashicons-arrow-down',
        'ArrowLeft'        => 'dashicons-arrow-left',
        'ArrowRight'       => 'dashicons-arrow-right',
        'External'         => 'dashicons-external',
        'Share'            => 'dashicons-share',
        'Email'            => 'dashicons-email',
        'Phone'            => 'dashicons-phone',
        'Location'         => 'dashicons-location',
        'Map'              => 'dashicons-location-alt',
        'Filter'           => 'dashicons-filter',
        'Sort'             => 'dashicons-sort',
        'List'             => 'dashicons-list-view',
        'Grid'             => 'dashicons-grid-view',
        'Collapse'         => 'dashicons-admin-collapse',
        'Expand'           => 'dashicons-admin-collapse',
        'Help'             => 'dashicons-editor-help',
        'Cloud'            => 'dashicons-cloud',
        'Backup'           => 'dashicons-backup',
        'Rss'              => 'dashicons-rss',
        'Twitter'          => 'dashicons-twitter',
        'Facebook'         => 'dashicons-facebook',
        'Google'           => 'dashicons-google',
        'WordPress'        => 'dashicons-wordpress',
        'Wordpress'        => 'dashicons-wordpress',
        'Logo'             => 'dashicons-wordpress',
    ];

    /**
     * Get dashicons class for an icon name.
     */
    public static function class(?string $icon): string
    {
        if ($icon === null || $icon === '') {
            return 'dashicons-admin-generic';
        }

        // Already a dashicons class
        if (str_starts_with($icon, 'dashicons-')) {
            return $icon;
        }

        return self::ICON_MAP[$icon] ?? 'dashicons-admin-generic';
    }

    /**
     * Render a dashicon HTML span.
     */
    public static function render(?string $icon, array $attrs = []): string
    {
        $class = self::class($icon);

        $attrString = '';
        foreach ($attrs as $key => $value) {
            $attrString .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars((string) $value) . '"';
        }

        return sprintf(
            '<span class="dashicons %s"%s aria-hidden="true"></span>',
            $class,
            $attrString
        );
    }

    /**
     * Render menu image div (WordPress admin menu style).
     */
    public static function menuImage(?string $icon): string
    {
        $class = self::class($icon);
        return sprintf(
            '<div class="wp-menu-image dashicons-before %s" aria-hidden="true"><br /></div>',
            $class
        );
    }

    /**
     * Render admin bar icon.
     */
    public static function adminBarIcon(?string $icon): string
    {
        $class = self::class($icon);
        return sprintf(
            '<span class="ab-icon dashicons-before %s" aria-hidden="true"></span>',
            $class
        );
    }

    /**
     * Get all available icon names.
     */
    public static function availableIcons(): array
    {
        return array_keys(self::ICON_MAP);
    }
}
