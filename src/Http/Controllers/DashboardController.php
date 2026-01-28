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
        // Look in Top Level Menus
        foreach ($menus as $menu) {
            if ($menu->menuSlug === $slug && $menu->callback) {
                $content = $this->renderer->renderHybrid($menu->callback);
                return Response::html($this->renderAdminLayout($menu->pageTitle, $content, $menus));
            }
            
            // Look in Sub Menus
            $subs = $this->menuRepo->getSubMenus($menu->menuSlug);
            foreach ($subs as $sub) {
                if ($sub->menuSlug === $slug && $sub->callback) {
                    $content = $this->renderer->renderHybrid($sub->callback);
                    return Response::html($this->renderAdminLayout($sub->pageTitle, $content, $menus));
                }
            }
        }

        return Response::html($this->renderAdminLayout('Error', "<div class='error'><p>Standard WordPress Admin Page or Plugin page not found.</p></div>", $menus));
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
            <link rel='stylesheet' href='https://cdn.jsdelivr.net/gh/WordPress/dashicons/icon-assets/dashicons.css'>
            <link rel='stylesheet' href='https://fonts.googleapis.com/css?family=Open+Sans:400,600&display=swap'>
            <style>
                body { background: #f0f0f1; color: #3c434a; font-family: \"Open Sans\",sans-serif; margin: 0; font-size: 13px; }
                #wpwrap { height: auto; min-height: 100vh; width: 100%; position: relative; }
                
                /* Admin Bar */
                #wpadminbar { height: 32px; background: #1d2327; color: #c3c4c7; width: 100%; position: fixed; top: 0; left: 0; z-index: 99999; }
                
                /* Menu Sidebar */
                #adminmenumain { width: 160px; background: #1d2327; position: fixed; top: 32px; bottom: 0; left: 0; z-index: 9990; }
                #adminmenu { list-style: none; margin: 0; padding: 12px 0 0; }
                #adminmenu li { margin: 0; padding: 0; position: relative; }
                #adminmenu a { display: block; padding: 0; color: #f0f0f1; text-decoration: none; font-size: 14px; line-height: 1.3; }
                
                #adminmenu li.menu-top { min-height: 34px; border: none; }
                #adminmenu li.menu-top > a { padding: 8px 12px 8px 0; display: block; }
                
                /* Active Menu State */
                #adminmenu li.wp-has-current-submenu > a, 
                #adminmenu li.current > a { background: #2271b1 !important; color: #fff !important; }
                #adminmenu li.wp-has-current-submenu .wp-menu-image:before,
                #adminmenu li.current .wp-menu-image:before { color: #fff !important; }
                
                /* Arrow Indicator */
                .wp-menu-arrow { display: none; content: \"\"; position: absolute; right: 0; top: 7px; width: 0; height: 0; border-top: 10px solid transparent; border-bottom: 10px solid transparent; border-right: 10px solid #f0f0f1; z-index: 10; }
                #adminmenu li.wp-has-current-submenu .wp-menu-arrow,
                #adminmenu li.current .wp-menu-arrow { display: block; }
                
                #adminmenu li.menu-top:hover { background: #2c3338; color: #72aee6; }
                #adminmenu li.menu-top:hover .wp-menu-image:before { color: #72aee6; }

                #adminmenu .wp-menu-image { float: left; width: 36px; height: 34px; text-align: center; color: #a7aaad; display: flex; align-items: center; justify-content: center; }
                #adminmenu .wp-menu-image:before { font: normal 20px/1 dashicons !important; display: inline-block; -webkit-font-smoothing: antialiased; }
                #adminmenu .wp-menu-name { padding: 8px 0; }

                /* Submenu */
                .wp-submenu { list-style: none; margin: 0; padding: 7px 0 8px; background: #2c3338; }
                .wp-submenu li { padding: 0; }
                .wp-submenu a { color: rgba(240,240,241,.7); font-size: 13px; padding: 5px 0 5px 36px; display: block; }
                .wp-submenu a:hover { color: #72aee6; }
                .wp-submenu li.current a { color: #fff; font-weight: 600; }
                
                /* Hover Submenu behavior (for closed menus) */
                #adminmenu li.menu-top:not(.wp-menu-open):hover .wp-submenu { 
                    display: block !important; 
                    position: absolute; 
                    left: 160px; 
                    top: 0; 
                    width: 160px; 
                    box-shadow: 0 3px 5px rgba(0,0,0,0.2);
                    padding: 10px 0;
                }
                #adminmenu li.menu-top:not(.wp-menu-open):hover .wp-submenu li a { padding-left: 15px; }

                /* Notifications */
                .update-plugins { display: inline-block; background: #d63638; color: #fff; border-radius: 10px; padding: 0 6px; font-size: 9px; line-height: 17px; font-weight: 600; margin-left: 4px; vertical-align: top; }
                
                /* Content Area */
                #wpcontent { margin-left: 160px; padding-top: 32px; flex: 1; }
                #wpbody-content { padding: 0 20px 20px; }
                .wrap { margin: 10px 20px 0 2px; }
                .wrap h1 { font-size: 23px; font-weight: 400; margin: 0; padding: 9px 0 4px; line-height: 1.3; color: #1d2327; }
                
                /* Classic WP Elements */
                .wp-list-table { border: 1px solid #c3c4c7; border-spacing: 0; width: 100%; clear: both; background: #fff; margin-top: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
                .wp-list-table th, .wp-list-table td { padding: 8px 10px; border-bottom: 1px solid #f0f0f1; text-align: left; vertical-align: top; }
                .wp-list-table thead td, .wp-list-table thead th { border-bottom: 1px solid #c3c4c7; background: #fff; font-weight: 600; }
                
                .button-primary { background: #2271b1; border-color: #2271b1; color: #fff; text-decoration: none; text-shadow: none; display: inline-block; padding: 4px 12px; border-radius: 3px; border-width: 1px; border-style: solid; cursor: pointer; font-size: 13px; line-height: 2; height: 32px; min-height: 32px; }
                .button-primary:hover { background: #135e96; border-color: #135e96; }
                
                /* Dashboard Widgets */
                .metabox-holder { display: flex; flex-wrap: wrap; margin-top: 20px; gap: 20px; }
                .postbox { border: 1px solid #c3c4c7; background: #fff; width: calc(50% - 10px); min-width: 300px; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
                .postbox .postbox-header { border-bottom: 1px solid #f0f0f1; padding: 0 12px; height: 36px; display: flex; align-items: center; }
                .postbox .postbox-header h2 { font-size: 14px; margin: 0; font-weight: 600; }
                .postbox .inside { padding: 12px; margin: 0; }
                
                /* Form Table */
                .form-table { border-collapse: collapse; margin-top: .5em; width: 100%; clear: both; }
                .form-table th { vertical-align: top; text-align: left; padding: 20px 10px 20px 0; width: 200px; font-weight: 600; }
                .form-table td { margin-bottom: 9px; padding: 15px 10px; line-height: 1.3; vertical-align: middle; }
                input[type=text], input[type=email] { border: 1px solid #8c8f94; border-radius: 4px; padding: 0 8px; min-height: 30px; width: 25em; box-shadow: inset 0 1px 2px rgba(0,0,0,.07); }
            </style>
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
