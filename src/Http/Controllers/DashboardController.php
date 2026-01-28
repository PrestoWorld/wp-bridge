<?php

declare(strict_types=1);

namespace Prestoworld\Bridge\WordPress\Http\Controllers;

use Witals\Framework\Http\Response;
use PrestoWorld\Admin\MenuRepository;
use PrestoWorld\Admin\DashboardWidgetRepository;
use Prestoworld\Bridge\WordPress\Admin\AdminRenderer;
use PrestoWorld\Theme\ThemeManager;

class DashboardController
{
    public function __construct(
        protected MenuRepository $menuRepo,
        protected DashboardWidgetRepository $widgetRepo,
        protected AdminRenderer $renderer,
        protected ThemeManager $themeManager
    ) {}

    public function index(): Response
    {
        $GLOBALS['__presto_admin_context'] = ['driver' => 'wordpress', 'screen' => 'dashboard'];
        
        $widgets = $this->widgetRepo->getWidgets();
        $menus = $this->menuRepo->getMenus();

        // Render widgets output
        $widgetsOutput = [];
        foreach ($widgets as $widget) {
            $widgetsOutput[$widget->widgetId] = [
                'name' => $widget->widgetName,
                'content' => $this->renderer->renderHybrid($widget->callback, $widget->callbackArgs)
            ];
        }

        $html = $this->renderAdminLayout('Dashboard', $this->renderDashboardGrid($widgetsOutput), $menus);

        return Response::html($html);
    }

    public function show(string $page, \Witals\Framework\Http\Request $request): Response
    {
        $page = str_replace('.php', '', $page);
        $GLOBALS['__presto_admin_context'] = ['driver' => 'wordpress', 'screen' => $page];
        $menus = $this->menuRepo->getMenus();
        
        // Handle admin.php?page=... or any other page?page=...
        $pluginPage = $request->query('page');
        if ($pluginPage) {
            return $this->handlePluginPage($pluginPage, $menus);
        }

        // Handle standard WordPress pages
        return match ($page) {
            'edit' => $this->handleEditPage($request, $menus),
            'plugins' => $this->handlePluginsPage($menus),
            'options-general' => $this->handleOptionsPage($menus),
            default => Response::html($this->renderAdminLayout(ucwords($page), "<p>Simulation for {$page}.php is not yet fully implemented.</p>", $menus))
        };
    }

    public function saveOptions(\Witals\Framework\Http\Request $request): Response
    {
        // TODO: Implement settings saving logic
        return Response::redirect($request->header('referer') ?: '/wp-admin');
    }

    public function ajax(): Response
    {
        return Response::json(['success' => true, 'message' => 'Admin Ajax Simulated']);
    }

    protected function handlePluginPage(string $slug, array $menus): Response
    {
        // 1. Ensure WP is loaded & Hooks are fired FIRST
        $wpLoader = app(\Prestoworld\Bridge\WordPress\WordPressLoader::class);
        if (!$wpLoader->isLoaded()) {
            $wpLoader->load();
        }

        // Ensure admin menu hooks are fired
        if (\function_exists('did_action') && !\did_action('admin_menu')) {
            \do_action('admin_menu');
        }

        // 2. Refresh Menus from Repository (Pickup newly registered plugin pages)
        $menus = $this->menuRepo->getMenus();

        // 3. Look in PrestoWorld Menu Repository
        foreach ($menus as $menu) {
            if ($menu->menuSlug === $slug && $menu->callback) {
                if (is_callable($menu->callback)) {
                    $content = $this->renderer->renderHybrid($menu->callback);
                } else {
                    $content = "<div class='notice notice-error'><p>Error: Callback function <code>" . (is_string($menu->callback) ? $menu->callback : 'Array') . "</code> not found.</p></div>";
                }
                return Response::html($this->renderAdminLayout($menu->pageTitle, $content, $menus));
            }
            
            $subs = $this->menuRepo->getSubMenus($menu->menuSlug);
            foreach ($subs as $sub) {
                if ($sub->menuSlug === $slug && $sub->callback) {
                    if (is_callable($sub->callback)) {
                        $content = $this->renderer->renderHybrid($sub->callback);
                    } else {
                        $content = "<div class='notice notice-error'><p>Error: Callback function <code>" . (is_string($sub->callback) ? $sub->callback : 'Array') . "</code> not found.</p></div>";
                    }
                    return Response::html($this->renderAdminLayout($sub->pageTitle, $content, $menus));
                }
            }
        }

        // 4. Fallback: Look in WordPress Globals ($menu, $submenu)
        global $menu, $submenu;
        
        // Check Top Level
        if (isset($menu)) {
            foreach ($menu as $item) {
                // $item structure: [0 => Name, 1 => Cap, 2 => Slug/Callback, 3 => Page Title, ... ]
                if (isset($item[2]) && $item[2] === $slug) {
                    return $this->renderWPCallbackPage($slug, $item[3] ?? $item[0], $menus);
                }
            }
        }

        // Check Submenus
        if (isset($submenu)) {
            foreach ($submenu as $parent => $items) {
                foreach ($items as $item) {
                    // $item structure: [0 => Title, 1 => Cap, 2 => Slug, 3 => Page Title ]
                    if (isset($item[2]) && $item[2] === $slug) {
                         return $this->renderWPCallbackPage($slug, $item[3] ?? $item[0], $menus);
                    }
                }
            }
        }

        $safeSlug = \function_exists('esc_html') ? \esc_html($slug) : \htmlspecialchars($slug);
        return Response::html($this->renderAdminLayout('Error', "<div class='error'><p>Standard WordPress Admin Page or Plugin page not found: <code>".$safeSlug."</code></p></div>", $menus));
    }

    protected function renderWPCallbackPage(string $slug, string $title, array $menus): Response
    {
        if (!\function_exists('do_action')) {
            return Response::html($this->renderAdminLayout('Error', "<div class='error'><p>WordPress core could not be loaded.</p></div>", $menus));
        }

        ob_start();
        do_action($slug); // Some plugins hook direct to slug? No usually it's derived.
        
        // Try to find the hookname associated with this page
        // add_menu_page calls add_action( $hookname, $function );
        // The hookname is returned by add_menu_page but not stored easily in global $menu directly as callback.
        // WP stores it in global $admin_page_hooks['slug'].
        global $admin_page_hooks;
        
        if (isset($admin_page_hooks[$slug])) {
            $hook_name = $admin_page_hooks[$slug];
            // Render
            do_action($hook_name);
        } else {
            // Fallback: maybe the slug itself is an action (old style) or check 'toplevel_page_$slug'
            if (has_action('toplevel_page_' . $slug)) {
                do_action('toplevel_page_' . $slug);
            } elseif (has_action($slug)) {
                do_action($slug);
            } else {
                 echo "<p>Could not locate callback for slug: {$slug}</p>";
            }
        }

        $content = ob_get_clean();
        return Response::html($this->renderAdminLayout($title, $content, $menus));
    }

    protected function handleEditPage(\Witals\Framework\Http\Request $request, array $menus): Response
    {
        $postType = $request->query('post_type', 'post');
        $orm = app(\Cycle\ORM\ORMInterface::class);
        $repo = $orm->getRepository(\App\Models\Post::class);
        
        $posts = $repo->select()->where('type', $postType)->limit(20)->fetchAll();
        
        $table = "<table class='wp-list-table widefat fixed striped posts'>
            <thead><tr><th>Title</th><th>Author</th><th>Date</th></tr></thead>
            <tbody>";
        foreach ($posts as $post) {
            $table .= "<tr>
                <td><strong><a href='post.php?post={$post->id}&action=edit'>{$post->title}</a></strong></td>
                <td>admin</td>
                <td>".date('Y/m/d')."</td>
            </tr>";
        }
        $table .= "</tbody></table>";

        return Response::html($this->renderAdminLayout("Edit " . ucwords($postType . 's'), $table, $menus));
    }

    protected function handlePluginsPage(array $menus): Response
    {
        $content = "<h3>Installed Plugins</h3><p>Simulating active extensions...</p>";
        $content .= "<ul><li><strong>WP Bridge</strong> (Active)</li><li><strong>PrestoWorld Core</strong> (Active)</li></ul>";
        return Response::html($this->renderAdminLayout('Plugins', $content, $menus));
    }

    protected function handleOptionsPage(array $menus): Response
    {
        $content = "<h3>General Settings</h3><form method='post' action='options.php'>";
        $content .= "<table class='form-table'>
            <tr><th>Site Title</th><td><input type='text' value='PrestoWorld Site'></td></tr>
            <tr><th>Admin Email</th><td><input type='email' value='admin@example.com'></td></tr>
        </table>";
        $content .= "<p><button class='button-primary'>Save Changes</button></p></form>";
        return Response::html($this->renderAdminLayout('Settings', $content, $menus));
    }

    protected function renderDashboardGrid(array $widgetsOutput): string
    {
        $widgetsHtml = '';
        foreach ($widgetsOutput as $id => $data) {
            $widgetsHtml .= "
                <div class='postbox' id='{$id}'>
                    <div class='postbox-header'>
                        <h2>{$data['name']}</h2>
                    </div>
                    <div class='inside'>{$data['content']}</div>
                </div>
            ";
        }
        return "<div class='metabox-holder'>{$widgetsHtml}</div>";
    }

    protected function renderAdminLayout(string $title, string $content, array $menus): string
    {
        $currentScreen = $GLOBALS['__presto_admin_context']['screen'] ?? 'dashboard';

        $standardMenus = [
            'index' => [
                'label' => 'Dashboard',
                'icon' => 'dashicons-dashboard',
            ],
            'jankx' => [
                'label' => 'Jankx',
                'icon' => 'dashicons-admin-generic',
            ],
            'edit' => [
                'label' => 'Posts',
                'icon' => 'dashicons-admin-post',
                'subs' => ['edit' => 'All Posts', 'post-new' => 'Add New', 'edit-tags?taxonomy=category' => 'Categories', 'edit-tags?taxonomy=post_tag' => 'Tags']
            ],
            'upload' => [
                'label' => 'Media',
                'icon' => 'dashicons-admin-media',
                'subs' => ['upload' => 'Library', 'media-new' => 'Add New']
            ],
            'edit.php?post_type=page' => [
                'label' => 'Pages',
                'icon' => 'dashicons-admin-page',
                'subs' => ['edit.php?post_type=page' => 'All Pages', 'post-new.php?post_type=page' => 'Add New']
            ],
            'edit-comments' => [
                'label' => 'Comments',
                'icon' => 'dashicons-admin-comments',
            ],
            'themes' => [
                'label' => 'Appearance',
                'icon' => 'dashicons-admin-appearance',
                'subs' => ['themes' => 'Themes', 'customize' => 'Customize', 'widgets' => 'Widgets', 'nav-menus' => 'Menus']
            ],
            'plugins' => [
                'label' => 'Plugins',
                'icon' => 'dashicons-admin-plugins',
                'subs' => ['plugins' => 'Installed Plugins', 'plugin-install' => 'Add New', 'plugin-editor' => 'Plugin Editor']
            ],
            'users' => [
                'label' => 'Users',
                'icon' => 'dashicons-admin-users',
                'subs' => ['users' => 'All Users', 'user-new' => 'Add New', 'profile' => 'Profile']
            ],
            'tools' => [
                'label' => 'Tools',
                'icon' => 'dashicons-admin-tools',
                'subs' => ['tools' => 'Available Tools', 'import' => 'Import', 'export' => 'Export', 'site-health' => 'Site Health']
            ],
            'options-general' => [
                'label' => 'Settings',
                'icon' => 'dashicons-admin-settings',
                'subs' => [
                    'options-general' => 'General',
                    'options-writing' => 'Writing',
                    'options-reading' => 'Reading',
                    'options-discussion' => 'Discussion',
                    'options-media' => 'Media',
                    'options-permalink' => 'Permalinks',
                    'options-privacy' => 'Privacy'
                ]
            ],
        ];

        $menuHtml = '';
        $adminUrl = '/wp-admin/';
        foreach ($standardMenus as $slug => $data) {
            $isExactMatch = ($currentScreen === $slug);
            $isSubMatch = isset($data['subs']) && array_key_exists($currentScreen, $data['subs']);
            $isActive = $isExactMatch || $isSubMatch;

            $activeClass = $isActive ? 'wp-has-current-submenu wp-menu-open current' : 'wp-not-current-submenu';
            $urlToken = (str_contains($slug, '.php') || str_contains($slug, '?')) ? $slug : $slug . '.php';
            $fullUrl = $adminUrl . $urlToken;

            $updateHtml = ($slug === 'plugins') ? " <span class='update-plugins count-1'><span class='plugin-count'>1</span></span>" : "";

            $menuHtml .= "<li class='menu-top {$activeClass}'>
                <a href='{$fullUrl}' class='menu-top {$activeClass}'>
                    <div class='wp-menu-arrow'><div></div></div>
                    <div class='wp-menu-image dashicons-before {$data['icon']}'></div>
                    <div class='wp-menu-name'>{$data['label']}{$updateHtml}</div>
                </a>";

            if (isset($data['subs'])) {
                $displayStyle = $isActive ? 'display:block;' : 'display:none;';
                $menuHtml .= "<ul class='wp-submenu wp-submenu-wrap' style='{$displayStyle}'>";
                foreach ($data['subs'] as $subSlug => $subLabel) {
                    $subActiveClass = ($currentScreen === $subSlug) ? 'current' : '';
                    $subUrlToken = (str_contains($subSlug, '.php') || str_contains($subSlug, '?')) ? $subSlug : $subSlug . '.php';
                    $subFullUrl = $adminUrl . $subUrlToken;
                    $menuHtml .= "<li class='{$subActiveClass}'><a href='{$subFullUrl}' class='{$subActiveClass}'>{$subLabel}</a></li>";
                }
                $menuHtml .= "</ul>";
            }
            $menuHtml .= "</li>";
        }

        // Custom Menus from Registry
        foreach ($menus as $menu) {
            $isActive = ($currentScreen === $menu->menuSlug);
            $activeClass = $isActive ? 'wp-has-current-submenu wp-menu-open current' : 'wp-not-current-submenu';
            
            $menuHtml .= "<li class='menu-top {$activeClass}'>
                <a href='{$adminUrl}admin.php?page={$menu->menuSlug}' class='menu-top {$activeClass}'>
                    <div class='wp-menu-arrow'><div></div></div>
                    <div class='wp-menu-image dashicons-before dashicons-admin-generic'></div>
                    <div class='wp-menu-name'>{$menu->menuTitle}</div>
                </a>";
            
            $subs = $this->menuRepo->getSubMenus($menu->menuSlug);
            if (!empty($subs)) {
                 $displayStyle = $isActive ? 'display:block;' : 'display:none;';
                 $menuHtml .= "<ul class='wp-submenu wp-submenu-wrap' style='{$displayStyle}'>";
                 foreach ($subs as $sub) {
                     $subActiveClass = ($currentScreen === $sub->menuSlug) ? 'current' : '';
                     $menuHtml .= "<li class='{$subActiveClass}'><a href='{$adminUrl}admin.php?page={$sub->menuSlug}' class='{$subActiveClass}'>{$sub->menuTitle}</a></li>";
                 }
                 $menuHtml .= "</ul>";
            }
            $menuHtml .= "</li>";
        }

        return "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <title>{$title} &lsaquo; PrestoWorld &#8212; WordPress</title>
            <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/@icon/dashicons@0.9.7/dashicons.min.css'>
            <link rel='stylesheet' href='https://fonts.googleapis.com/css?family=Open+Sans:400,600&display=swap'>
            <link rel='stylesheet' href='/css/admin-core.css'>
            <!-- PrestoWorld SolidJS Admin Core -->
            <script type=\"module\" src=\"/js/admin-solid-core.js\"></script>
        </head>
        <body class='wp-admin wp-core-ui'>
            <div id='wpwrap'>
                <div id='wpadminbar'>
                    <div style='padding: 0 20px; line-height: 32px; font-size: 13px;'>
                        <a href='/' style='color:#fff; text-decoration:none;'><span class='dashicons dashicons-wordpress' style='vertical-align:middle;'></span> PrestoWorld</a> &nbsp; | &nbsp; Howdy, admin
                    </div>
                </div>
                <div id='adminmenumain'>
                    <ul id='adminmenu'>
                        {$menuHtml}
                    </ul>
                </div>
                <div id='wpcontent'>
                    <div id='wpbody-content'>
                        <div class='wrap'>
                            <h1>{$title}</h1>
                            {$content}
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
