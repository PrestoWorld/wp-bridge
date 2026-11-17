<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Admin\Controllers;

use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use PrestoWorld\Contracts\Admin\Menu\MenuContextRepository;
use PrestoWorld\Contracts\Admin\Dashboard\DashboardWidgetRepository;
use PrestoWorld\Admin\ScreenOptions\ScreenOption;
use PrestoWorld\Admin\ScreenOptions\ScreenOptionsContext;
use PrestoWorld\Admin\AdminBar\AdminBarItem;
use PrestoWorld\Admin\AdminBar\AdminBarContext;
use Witals\Framework\Contracts\Container;
use PrestoWorld\Bridge\WordPress\Admin\Integration\SharedAdminBridge;

class WpAdminController
{
    protected const WIDGET_COMPONENTS = [
        'at-a-glance'  => 'StatCards',
        'quick-draft'  => 'ActivityLog',
        'activity'     => 'ActivityLog',
        'events-news'  => 'EventsNews',
    ];

    protected const WP_SCREEN_MAP = [
        'index.php'           => 'dashboard',
        'admin.php'           => null,
        'edit.php'            => 'posts',
        'post-new.php'        => 'post-new',
        'post.php'            => 'post',
        'upload.php'          => 'upload',
        'media-new.php'       => 'media-new',
        'edit-pages.php'      => 'edit-pages',
        'edit-comments.php'   => 'edit-comments',
        'themes.php'          => 'themes',
        'customize.php'       => 'customize',
        'widgets.php'         => 'widgets',
        'nav-menus.php'       => 'nav-menus',
        'theme-editor.php'    => 'theme-editor',
        'plugins.php'         => 'plugins',
        'plugin-install.php'  => 'plugin-install',
        'plugin-editor.php'   => 'plugin-editor',
        'users.php'           => 'users',
        'user-new.php'        => 'user-new',
        'user-edit.php'       => 'user-edit',
        'profile.php'         => 'profile',
        'tools.php'           => 'tools',
        'import.php'          => 'import',
        'export.php'          => 'export',
        'site-health.php'     => 'site-health',
        'site-health-info.php'=> 'site-health-info',
        'options-general.php' => 'settings',
        'options-writing.php' => 'options-writing',
        'options-reading.php' => 'options-reading',
        'options-discussion.php' => 'options-discussion',
        'options-media.php'   => 'options-media',
        'options-permalink.php' => 'options-permalink',
        'options-privacy.php' => 'options-privacy',
        'update-core.php'     => 'update-core',
    ];

    public function __construct(
        protected Container $container,
        protected MenuContextRepository $menu,
        protected DashboardWidgetRepository $widgets,
    ) {}

    public function __invoke(Request $request): Response
    {
        $path = $request->path();
        $skin = $this->resolveSkin($path, $request);
        $screenId = $this->resolveScreenId($request);

        $menuSections = $this->buildMenuSections();
        $screens = $this->buildScreens($menuSections);

        $screenTitle = 'Dashboard';
        foreach ($screens as $s) {
            if (($s['id'] ?? '') === $screenId) {
                $screenTitle = $s['title'] ?? 'Dashboard';
                break;
            }
        }

        $initialState = [
            'user' => $this->getUserState(),
            'screens' => $screens,
            'menuSections' => $menuSections,
            'widgets' => $this->buildWidgets(),
            'screenOptions' => $this->buildScreenOptions(),
            'adminBar' => $this->buildAdminBar()->toArray(),
            'page' => [
                'path' => $request->path(),
                'title' => $screenTitle,
                'screenId' => $screenId,
            ],
        ];

        $html = $skin->renderLayout('', [
            'title' => $screenTitle,
            'initialState' => $initialState,
        ]);

        return Response::html($html);
    }

    protected function resolveSkin(string $path, Request $request): mixed
    {
        $skins = $this->container->make(\PrestoWorld\Admin\SkinManager::class);

        if (str_starts_with($path, '/wp-admin')) {
            if ($skins->hasSkin('wordpress-classic')) {
                return $skins->getSkin('wordpress-classic');
            }
        }

        $querySkin = $request->query('skin');
        if ($querySkin !== null && $skins->hasSkin($querySkin)) {
            return $skins->getSkin($querySkin);
        }

        return $skins->getActiveSkin();
    }

    protected function resolveScreenId(Request $request): string
    {
        $path = $request->path();
        $scriptName = basename($path);

        $queryScreen = $request->query('screen');
        if ($queryScreen !== null) {
            return $queryScreen;
        }

        if (isset(self::WP_SCREEN_MAP[$scriptName])) {
            $mapped = self::WP_SCREEN_MAP[$scriptName];
            if ($mapped !== null) {
                return $mapped;
            }
            return $request->query('page', 'dashboard');
        }

        if (in_array($path, ['/wp-admin', '/wp-admin/'], true)) {
            return 'dashboard';
        }

        return 'dashboard';
    }

    protected function buildScreens(array $menuSections): array
    {
        $screens = [];
        $seen = [];

        foreach ($menuSections as $section) {
            foreach ($section['items'] ?? [] as $item) {
                $screenId = $item['screenId'] ?? null;
                if ($screenId !== null && !isset($seen[$screenId])) {
                    $seen[$screenId] = true;
                    $screens[] = [
                        'id' => $screenId,
                        'title' => $item['label'],
                        'icon' => $item['icon'] ?? 'Circle',
                        'position' => $item['priority'] ?? count($screens) * 10,
                    ];
                }
            }
        }

        return $screens;
    }

    protected function buildMenuSections(): array
    {
        // Ensure WordPress core menus are present in the standardized menu stack.
        $coreSections = [];
        try {
            $coreSections = \PrestoWorld\Bridge\WordPress\Admin\Menu\WordPressCoreMenu::buildClassicSections($this->menu);
        } catch (\Throwable $e) {
        }

        $menuTree = [];
        try {
            $bridge = new SharedAdminBridge($this->container);
            $shared = $bridge->getSharedMenuTree();
            if (is_array($shared) && !empty($shared)) {
                $menuTree = $shared;
            }
        } catch (\Throwable $e) {
        }

        // Always include repository menus (modules/extensions registered there).
        $repoSections = [];
        try {
            $repoSections = $this->normalizeClassicSections($this->menu->getTree());
        } catch (\Throwable $e) {
            $repoSections = [];
        }

        $sharedSections = [];
        if (!empty($menuTree)) {
            $sharedSections = $this->normalizeClassicSections($menuTree);
        }

        // Merge: WordPress core first, then repo, then shared (dedupe by id).
        $merged = [];
        $seen = [];
        foreach (array_merge($coreSections, $repoSections, $sharedSections) as $section) {
            if (!is_array($section)) {
                continue;
            }
            $id = (string) ($section['id'] ?? '');
            if ($id !== '' && isset($seen[$id])) {
                continue;
            }
            if ($id !== '') {
                $seen[$id] = true;
            }
            $merged[] = $section;
        }

        // For classic wp-admin, keep WordPress-like menu groups (core + shim-registered menu-*).
        $merged = array_values(array_filter($merged, static function (array $section): bool {
            $id = (string) ($section['id'] ?? '');
            return str_starts_with($id, 'menu-');
        }));

        usort($merged, static fn(array $a, array $b) => ($a['priority'] ?? 0) <=> ($b['priority'] ?? 0));
        return $merged;
    }

    /**
     * Normalize repository/core menu trees into the flat classic layout shape.
     *
     * @param array<mixed> $tree
     * @return list<array<string,mixed>>
     */
    protected function normalizeClassicSections(array $tree): array
    {
        $sections = [];
        foreach ($tree as $i => $node) {
            if (!is_array($node)) {
                continue;
            }
            // Standard MenuContextRepository tree: section -> groups -> children/items
            if (isset($node['groups']) && is_array($node['groups'])) {
                foreach ($node['groups'] as $group) {
                    if (!is_array($group)) {
                        continue;
                    }
                    $sections[] = $this->normalizeClassicGroup($group, $node);
                }
                continue;
            }
            $sections[] = $this->normalizeClassicGroup($node, []);
        }
        return $sections;
    }

    /**
     * @param array<string,mixed> $group
     * @param array<string,mixed> $parent
     * @return array<string,mixed>
     */
    protected function normalizeClassicGroup(array $group, array $parent): array
    {
        $children = $group['children'] ?? $group['items'] ?? [];
        $items = [];
        foreach (is_array($children) ? $children : [] as $child) {
            if (!is_array($child)) {
                continue;
            }
            $items[] = [
                'id' => $child['id'] ?? $child['screenId'] ?? '',
                'screenId' => $child['screenId'] ?? $child['id'] ?? '',
                'label' => $child['label'] ?? $child['title'] ?? '',
                'icon' => $child['icon'] ?? ($group['icon'] ?? ($parent['icon'] ?? 'dashicons-admin-generic')),
                'url' => $child['url'] ?? '',
                'priority' => $child['priority'] ?? 10,
            ];
        }
        usort($items, static fn(array $a, array $b) => ($a['priority'] ?? 0) <=> ($b['priority'] ?? 0));
        $first = $items[0] ?? [];
        return [
            'id' => $group['id'] ?? ($parent['id'] ?? 'section'),
            'title' => $group['label'] ?? $group['title'] ?? ($parent['label'] ?? ''),
            'priority' => $group['priority'] ?? ($parent['priority'] ?? 10),
            'icon' => $group['icon'] ?? ($parent['icon'] ?? 'dashicons-admin-generic'),
            'screenId' => $first['screenId'] ?? ($group['screenId'] ?? ($group['id'] ?? '')),
            'items' => $items,
        ];
    }

    protected function buildWidgets(): array
    {
        $definitions = [];

        foreach ($this->widgets->getWidgets() as $widget) {
            $id = $widget->getId();
            $component = self::WIDGET_COMPONENTS[$id] ?? '';

            $grid = match ($widget->getColumn()) {
                1 => 'half',
                2 => 'half',
                default => 'full',
            };

            $definitions[] = [
                'id' => $widget->getId(),
                'title' => $widget->getTitle(),
                'component' => $component,
                'grid' => $grid,
                'priority' => $widget->getPriority(),
                'visible' => $widget->isVisible(),
                'props' => [
                    'content' => $widget->getContent(),
                    'column' => $widget->getColumn(),
                ],
            ];
        }

        return $definitions;
    }

    protected function buildScreenOptions(): array
    {
        $postsOptions = new ScreenOptionsContext('posts', 'Posts Settings');
        $postsOptions->addOption(new ScreenOption(
            id: 'posts_per_page',
            label: 'Posts per page',
            type: 'number',
            default: 20,
        ));
        $postsOptions->addOption(new ScreenOption(
            id: 'show_comments',
            label: 'Show comment count',
            type: 'checkbox',
            default: true,
        ));

        $pluginsOptions = new ScreenOptionsContext('plugins', 'Plugins Settings');
        $pluginsOptions->addOption(new ScreenOption(
            id: 'show_deprecated',
            label: 'Show deprecated plugins',
            type: 'checkbox',
            default: false,
        ));

        return [
            $postsOptions->toArray(),
            $pluginsOptions->toArray(),
        ];
    }

    protected function buildAdminBar(): AdminBarContext
    {
        $bar = new AdminBarContext();

        $bar->addItem(new AdminBarItem(
            id: 'visit-site',
            label: 'Visit Site',
            icon: 'Globe',
            href: '/',
            type: 'link',
        ));

        $notification = new AdminBarItem(
            id: 'notifications',
            label: 'Notifications',
            icon: 'Bell',
            type: 'notification',
            badge: 3,
        );

        $bar->addItem(new AdminBarItem(
            id: 'new-post',
            label: 'New Post',
            icon: 'Plus',
            type: 'button',
        ));

        $bar->addItem($notification);

        return $bar;
    }

    public function adminAjax(Request $request): Response
    {
        $action = $request->query('action', $request->post('action', ''));
        return Response::json([
            'success' => false,
            'data' => [],
            'error' => "WP Ajax action '{$action}' is not implemented.",
        ]);
    }

    protected function getUserState(): array
    {
        return [
            'name' => 'Administrator',
            'role' => 'admin',
            'avatar' => null,
        ];
    }
}
