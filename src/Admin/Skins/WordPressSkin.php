<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Admin\Skins;

use PrestoWorld\Bridge\WordPress\Admin\Dashicons;
use PrestoWorld\Contracts\Admin\SkinInterface;
use Witals\Framework\Contracts\View\Factory as ViewFactory;

class WordPressSkin implements SkinInterface
{
    protected ViewFactory $view;
    protected string $namespace = 'wp-admin';

    /** WordPress-style URL for each screenId */
    public const SCREEN_URLS = [
        'dashboard' => 'index.php',
        'posts' => 'edit.php',
        'post-new' => 'post-new.php',
        'post' => 'post.php',
        'upload' => 'upload.php',
        'media-new' => 'media-new.php',
        'edit-pages' => 'edit-pages.php',
        'edit-comments' => 'edit-comments.php',
        'themes' => 'themes.php',
        'customize' => 'customize.php',
        'widgets' => 'widgets.php',
        'nav-menus' => 'nav-menus.php',
        'theme-editor' => 'theme-editor.php',
        'plugins' => 'plugins.php',
        'plugin-install' => 'plugin-install.php',
        'plugin-editor' => 'plugin-editor.php',
        'users' => 'users.php',
        'user-new' => 'user-new.php',
        'user-edit' => 'user-edit.php',
        'profile' => 'profile.php',
        'tools' => 'tools.php',
        'import' => 'import.php',
        'export' => 'export.php',
        'site-health' => 'site-health.php',
        'site-health-info' => 'site-health-info.php',
        'settings' => 'options-general.php',
        'options-writing' => 'options-writing.php',
        'options-reading' => 'options-reading.php',
        'options-discussion' => 'options-discussion.php',
        'options-media' => 'options-media.php',
        'options-permalink' => 'options-permalink.php',
        'options-privacy' => 'options-privacy.php',
        'update-core' => 'update-core.php',
    ];

    public function __construct(ViewFactory $view)
    {
        $this->view = $view;
        $this->view->addNamespace($this->namespace, __DIR__ . '/../../../templates/admin/wordpress');
    }

    public static function getManifest(): array
    {
        return [
            'name'        => 'WordPress Classic',
            'version'     => '1.1.0',
            'description' => 'Classic WordPress admin skin with 100% SSR rendering and dashicons support',
            'mode'        => SkinInterface::MODE_SSR,
            'assets'      => [
                'css' => ['wp-admin-css'],
                'js'  => [],
            ],
        ];
    }

    public function getName(): string
    {
        return 'wordpress-classic';
    }

    public function getRenderMode(): string
    {
        return SkinInterface::MODE_SSR;
    }

    public function getAssets(): array
    {
        $base = '/wp-admin/assets/css';
        $files = [
            'dashicons.css',
            'common.css',
            'forms.css',
            'admin-menu.css',
            'dashboard.css',
            'list-tables.css',
            'edit.css',
            'revisions.css',
            'media.css',
            'themes.css',
            'about.css',
            'nav-menus.css',
            'widgets.css',
            'site-icon.css',
            'l10n.css',
            'site-health.css',
        ];

        $css = array_map(
            fn (string $file): string => $base . '/' . $file,
            $files
        );
        $css[] = $base . '/colors/blue/colors.css';

        return [
            'css' => $css,
            'js'  => [],
        ];
    }

    public function renderLayout(string $content, array $args = []): string
    {
        $initialState = $args['initialState'] ?? [];

        $page        = $initialState['page'] ?? [];
        $menuSections = $initialState['menuSections'] ?? [];
        $adminBar    = $initialState['adminBar'] ?? [];
        $widgets     = $initialState['widgets'] ?? [];
        $screens     = $initialState['screens'] ?? [];
        $screenOptions = $initialState['screenOptions'] ?? [];
        $user        = $initialState['user'] ?? [];
        $activeScreen = $page['screenId'] ?? 'dashboard';

        return (string) $this->view->make("{$this->namespace}::layout", [
            'title'         => $args['title'] ?? 'Admin',
            'activeScreen'  => $activeScreen,
            'menuSections'  => $menuSections,
            'adminBar'      => $adminBar,
            'widgets'       => $widgets,
            'screens'       => $screens,
            'screenOptions' => $screenOptions,
            'user'          => $user,
            'initialState'  => $initialState,
            'page'          => $page,
            'content'       => $content,
        ]);
    }

    public function renderComponent(string $component, array $props = []): string
    {
        try {
            return (string) $this->view->make("{$this->namespace}::components.{$component}", $props);
        } catch (\Throwable) {
            return "<!-- component {$component} not found -->";
        }
    }

    public static function iconClass(?string $icon): string
    {
        return Dashicons::class($icon);
    }

    public static function screenUrl(string $screenId): string
    {
        return self::SCREEN_URLS[$screenId] ?? 'index.php';
    }
}
