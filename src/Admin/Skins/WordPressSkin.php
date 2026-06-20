<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Admin\Skins;

use PrestoWorld\Contracts\Admin\SkinInterface;
use Witals\Framework\Contracts\View\Factory as ViewFactory;

class WordPressSkin implements SkinInterface
{
    protected ViewFactory $view;
    protected string $namespace = 'wp-admin';

    /** WordPress-style URL for each screenId */
    public const SCREEN_URLS = [
        'dashboard' => 'index.php',
        'posts'     => 'edit.php',
        'plugins'   => 'plugins.php',
        'settings'  => 'options-general.php',
    ];

    protected const ICON_MAP = [
        'LayoutDashboard'  => 'dashicons-dashboard',
        'FileText'         => 'dashicons-admin-post',
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
            'version'     => '1.0.0',
            'description' => 'Classic WordPress admin skin with 100% SSR rendering',
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

    public function getAssets(): array
    {
        return [
            'css' => [
                'https://s.w.org/wp-includes/css/dashicons.min.css',
                'https://s.w.org/wp-admin/css/wp-admin.min.css',
            ],
            'js' => [],
        ];
    }

    public static function iconClass(?string $icon): string
    {
        return self::ICON_MAP[$icon] ?? 'dashicons-admin-generic';
    }

    public static function screenUrl(string $screenId): string
    {
        return self::SCREEN_URLS[$screenId] ?? 'index.php';
    }
}
