<?php
/**
 * @var string $title
 * @var string $activeScreen
 * @var array  $menuSections
 * @var array  $adminBar
 * @var array  $widgets
 * @var array  $screens
 * @var array  $screenOptions
 * @var array  $user
 * @var array  $initialState
 * @var array  $page
 * @var string $content
 */

// Extended screen URL map — overrides WordPressSkin::SCREEN_URLS
$__screenUrlMap = [
    'dashboard' => 'index.php',
    'posts'     => 'edit.php',
    'post-new'  => 'post-new.php',
    'post'      => 'post.php',
    'upload'    => 'upload.php',
    'media-new' => 'media-new.php',
    'edit-pages' => 'edit-pages.php',
    'edit-comments' => 'edit-comments.php',
    'themes'    => 'themes.php',
    'customize' => 'customize.php',
    'widgets'   => 'widgets.php',
    'nav-menus' => 'nav-menus.php',
    'theme-editor' => 'theme-editor.php',
    'plugins'   => 'plugins.php',
    'plugin-install' => 'plugin-install.php',
    'plugin-editor' => 'plugin-editor.php',
    'users'     => 'users.php',
    'user-new'  => 'user-new.php',
    'user-edit' => 'user-edit.php',
    'profile'   => 'profile.php',
    'tools'     => 'tools.php',
    'import'    => 'import.php',
    'export'    => 'export.php',
    'site-health' => 'site-health.php',
    'site-health-info' => 'site-health-info.php',
    'settings'  => 'options-general.php',
    'options-writing' => 'options-writing.php',
    'options-reading' => 'options-reading.php',
    'options-discussion' => 'options-discussion.php',
    'options-media' => 'options-media.php',
    'options-permalink' => 'options-permalink.php',
    'options-privacy' => 'options-privacy.php',
    'update-core' => 'update-core.php',
];
$__screenUrl = function (string $screenId) use ($__screenUrlMap): string {
    return $__screenUrlMap[$screenId] ?? \PrestoWorld\Bridge\WordPress\Admin\Skins\WordPressSkin::screenUrl($screenId);
};
?><!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> &lsaquo; PrestoWorld</title>

    <meta name="presto-cdn-base" content="" />
    <!-- CDN assets will be served from presto-cdn in production -->

    <style>
        /* ── Critical WordPress admin layout (no external CSS required) ── */
        * { box-sizing: border-box; }
        html { background: #f0f0f1; }

        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            font-size: 13px;
            color: #3c434a;
            background: #f0f0f1;
            min-height: 100vh;
            padding-top: 32px;
        }

        /* ── Admin Bar ─────────────────────────────────── */
        #wpadminbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 32px;
            z-index: 99999;
            background: #1d2327;
            color: #c3c4c7;
            font-size: 13px;
            line-height: 32px;
        }
        #wpadminbar .quicklinks { display: flex; justify-content: space-between; }
        #wpadminbar .ab-top-menu { display: flex; list-style: none; margin: 0; padding: 0; }
        #wpadminbar .ab-top-menu > li { position: relative; }
        #wpadminbar .ab-item {
            display: flex; align-items: center; gap: 4px;
            color: #c3c4c7; text-decoration: none;
            padding: 0 8px; line-height: 32px; height: 32px;
            white-space: nowrap;
        }
        #wpadminbar .ab-item:hover { color: #72aee6; }
        #wpadminbar .ab-icon { display: inline-block; width: 20px; text-align: center; font-size: 16px; }
        #wpadminbar .ab-label { font-size: 10px; background: #50575e; color: #fff; border-radius: 3px; padding: 0 5px; margin-left: 2px; }

        /* ── Main wrapper ───────────────────────────────── */
        #wpwrap { display: flex; min-height: calc(100vh - 32px); position: relative; }

        /* ── Admin Menu ─────────────────────────────────── */
        #adminmenumain { width: 160px; flex-shrink: 0; }
        #adminmenuback {
            position: fixed; top: 32px; bottom: 0; left: 0;
            width: 160px; background: #1d2327; z-index: 1;
        }
        #adminmenuwrap {
            position: relative; z-index: 2;
            width: 160px; padding-top: 0;
        }
        #adminmenu {
            list-style: none; margin: 0; padding: 0;
            background: #1d2327; min-height: 100vh;
        }
        #adminmenu .wp-menu-separator { height: 1px; margin: 6px 0; background: #2c3338; }
        #adminmenu .menu-top { position: relative; }
        #adminmenu .menu-top > a {
            display: flex; align-items: center; gap: 6px;
            padding: 6px 12px; color: #c3c4c7; text-decoration: none;
            font-size: 13px; line-height: 1.4; min-height: 34px;
            position: relative;
        }
        #adminmenu .menu-top > a:hover { color: #72aee6; }
        #adminmenu .menu-top.wp-has-current-submenu > a { color: #fff; background: #2c3338; }
        #adminmenu .wp-menu-image {
            width: 20px; height: 20px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
        }
        #adminmenu .wp-menu-icon-placeholder {
            display: inline-block; width: 8px; height: 8px;
            border-radius: 50%; background: #787c82;
        }
        #adminmenu .menu-top.wp-has-current-submenu .wp-menu-icon-placeholder { background: #72aee6; }
        #adminmenu .wp-menu-name { padding: 0; }

        /* Submenu — hidden by default, shown on hover or when parent is active */
        #adminmenu .wp-submenu {
            display: none; list-style: none; margin: 0; padding: 4px 0 8px;
            background: #2c3338; font-size: 13px;
            border-radius: 0 0 4px 4px;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.04);
        }
        #adminmenu .wp-has-current-submenu .wp-submenu { display: block; }
        #adminmenu li.menu-top:hover .wp-submenu { display: block; }
        #adminmenu .wp-submenu-head { display: none; }
        #adminmenu .wp-submenu li { border: none; margin: 0; }
        #adminmenu .wp-submenu a {
            display: block; padding: 5px 12px 5px 28px; color: #9ca2a7; text-decoration: none;
            font-size: 13px; line-height: 1.5; transition: color 0.1s;
            border-left: 2px solid transparent;
        }
        #adminmenu .wp-submenu a:hover {
            color: #72aee6; border-left-color: #72aee6;
            background: rgba(114, 174, 230, 0.04);
        }
        #adminmenu .wp-submenu li.current a {
            color: #fff; font-weight: 600;
            border-left-color: #72aee6;
            background: rgba(255,255,255,0.03);
        }

        /* Submenu arrow indicator */
        #adminmenu .wp-submenu-arrow {
            position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
            font-size: 10px; color: #5c636a; transition: transform 0.15s, color 0.15s;
            line-height: 1;
        }
        #adminmenu .wp-has-current-submenu .wp-submenu-arrow { color: #72aee6; }
        #adminmenu li.menu-top:hover .wp-submenu-arrow { color: #c3c4c7; }
        #adminmenu .wp-has-current-submenu .wp-submenu-arrow,
        #adminmenu li.menu-top:hover .wp-submenu-arrow { transform: translateY(-50%) rotate(180deg); }

        /* ── Content Area ───────────────────────────────── */
        #wpcontent { flex: 1; margin-left: 0; min-width: 0; position: relative; }
        #wpbody { padding: 20px; }
        #wpbody-content { position: relative; }

        .wrap { margin: 0; }
        .wp-heading-inline { font-size: 23px; font-weight: 400; margin: 0 0 10px; padding: 0; line-height: 1.3; }
        .wp-header-end { border: none; margin: 10px 0; }
        hr.wp-header-end { border-top: 1px solid #dcdcde; }

        /* Notices */
        .notice { padding: 8px 12px; border-left: 4px solid #72aee6; background: #fff; margin: 5px 0 15px; font-size: 13px; }
        .notice-info { border-left-color: #72aee6; }
        .notice-warning { border-left-color: #dba617; }
        .notice-success { border-left-color: #46b450; }
        .notice-error { border-left-color: #d63638; }
        .notice p { margin: 0; }

        /* Screen options */
        .screen-meta-toggle { position: absolute; top: 0; right: 0; }
        #screen-meta { z-index: 10; background: #fff; border: 1px solid #dcdcde; border-top: none; padding: 10px; }
        .show-settings { background: #fff; border: 1px solid #dcdcde; border-top: none; padding: 4px 10px; cursor: pointer; font-size: 13px; }
        .metabox-prefs label { display: block; margin: 4px 0; }

        /* Postbox widgets */
        #dashboard-widgets-wrap { margin-top: 10px; }
        #dashboard-widgets { display: flex; gap: 2%; }
        .postbox-container { width: 49%; }
        .postbox { background: #fff; border: 1px solid #dcdcde; margin-bottom: 20px; }
        .postbox-header { border-bottom: 1px solid #dcdcde; padding: 8px 12px; }
        .postbox-header h2 { margin: 0; font-size: 14px; font-weight: 600; }
        .inside { padding: 12px; }

        /* Tables (list table, plugin table) */
        .wp-list-table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #dcdcde; }
        .wp-list-table th { text-align: left; padding: 8px 10px; border-bottom: 1px solid #dcdcde; font-weight: 600; font-size: 13px; }
        .wp-list-table td { padding: 8px 10px; border-bottom: 1px solid #f0f0f1; }
        .wp-list-table.striped tbody tr:nth-child(odd) { background: #f6f7f7; }
        .wp-list-table .check-column { width: 2.2em; text-align: center; }
        .tablenav { margin: 6px 0 4px; font-size: 13px; }
        .tablenav .actions { float: left; }
        .tablenav .actions select { margin-right: 4px; }
        .tablenav-pages { float: right; }
        .tablenav .clear { clear: both; }

        /* Form tables */
        .form-table { width: 100%; margin-top: 10px; border-collapse: collapse; }
        .form-table th { width: 200px; padding: 10px 10px 10px 0; text-align: left; vertical-align: top; font-weight: 600; }
        .form-table td { padding: 10px 0; }
        .form-table input.regular-text { width: 25em; padding: 4px 8px; font-size: 13px; border: 1px solid #8c8f94; border-radius: 4px; }
        .button { display: inline-block; padding: 4px 12px; border: 1px solid #8c8f94; border-radius: 3px; background: #fff; cursor: pointer; font-size: 13px; line-height: 2; }
        .button-primary { background: #2271b1; border-color: #2271b1; color: #fff; }
        .button-primary:hover { background: #135e96; border-color: #135e96; }
        .submit { padding: 10px 0; }

        .presto-content-area { min-height: 400px; }
    </style>

    <script>
    window.__INITIAL_STATE__ = <?= json_encode($initialState, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>
</head>
<body class="wp-admin wp-core-ui no-js multisite admin-color-fresh <?= 'screen-' . htmlspecialchars($activeScreen) ?>">

<?php
// ── Admin Bar ──────────────────────────────────────────────
$adminBarItems = $adminBar['items'] ?? [];
?>
<div id="wpadminbar" class="nojq">
    <div class="quicklinks" id="wp-toolbar" role="navigation" aria-label="Toolbar">
        <ul id="wp-admin-bar-root-default" class="ab-top-menu">
            <li id="wp-admin-bar-wp-logo" class="menupop">
                <a class="ab-item" aria-haspopup="true" href="/" tabindex="0">
                    <span class="ab-icon" aria-hidden="true"></span>
                    <span class="screen-reader-text">About PrestoWorld</span>
                </a>
            </li>
            <li id="wp-admin-bar-site-name" class="menupop">
                <a class="ab-item" aria-haspopup="true" href="/">PrestoWorld</a>
            </li>
        </ul>
        <ul id="wp-admin-bar-top-secondary" class="ab-top-menu">
            <?php foreach ($adminBarItems as $item): ?>
                <li id="wp-admin-bar-<?= htmlspecialchars($item['id'] ?? '') ?>">
                <?php if (($item['type'] ?? '') === 'link'): ?>
                <a class="ab-item" href="<?= htmlspecialchars($item['href'] ?? '#') ?>">
                    <?php if (!empty($item['icon'])): ?>
                    <span class="ab-icon" data-icon="<?= htmlspecialchars($item['icon']) ?>"></span>
                    <?php endif; ?>
                    <?= htmlspecialchars($item['label'] ?? '') ?>
                </a>
                <?php elseif (($item['type'] ?? '') === 'notification'): ?>
                <a class="ab-item" href="#">
                    <span class="ab-icon" data-icon="<?= htmlspecialchars($item['icon'] ?? 'Bell') ?>"></span>
                    <span class="ab-label"><?= htmlspecialchars((string)($item['badge'] ?? '')) ?></span>
                </a>
                <?php else: ?>
                <a class="ab-item" href="#">
                    <?= htmlspecialchars($item['label'] ?? '') ?>
                </a>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
            <li id="wp-admin-bar-my-account" class="menupop with-avatar">
                <a class="ab-item" aria-haspopup="true" href="#">
                    <?= htmlspecialchars($user['name'] ?? 'Admin') ?>
                </a>
            </li>
        </ul>
    </div>
</div>

<?php
// ── Main Wrapper ───────────────────────────────────────────
$currentScreenTitle = '';
$screenMap = [];
foreach ($screens as $s) {
    $screenMap[$s['id'] ?? ''] = $s['title'] ?? '';
}
$currentScreenTitle = $screenMap[$activeScreen] ?? 'Dashboard';
?>

<div id="wpwrap">

    <?php // ── Admin Menu ─────────────────────────────────── ?>
    <div id="adminmenumain" role="navigation" aria-label="Main menu">
        <div id="adminmenuback"></div>
        <div id="adminmenuwrap">
            <ul id="adminmenu">
            <?php
            $sectionIndex = 0;
            foreach ($menuSections as $section):
                $sectionItems = $section['items'] ?? [];
                if (empty($sectionItems)) continue;

                $firstItem = $sectionItems[0];
                $sectionScreenId = $section['screenId'] ?? $firstItem['screenId'] ?? '';
                $sectionIcon = $section['icon'] ?? $firstItem['icon'] ?? 'Circle';
                $sectionLabel = $section['title'] ?: $firstItem['label'] ?? '';
                $sectionHasChildren = count($sectionItems) > 1;

                $anyChildActive = false;
                foreach ($sectionItems as $item) {
                    if (($item['screenId'] ?? '') === $activeScreen) {
                        $anyChildActive = true;
                        break;
                    }
                }

                if ($sectionIndex > 0): ?>
                <li class="wp-menu-separator" role="presentation"><div class="separator"></div></li>
                <?php endif; ?>
                <li class="menu-top menu-icon-<?= htmlspecialchars($sectionScreenId) ?> <?= $anyChildActive ? 'wp-has-current-submenu wp-menu-open' : '' ?>">
                    <a href="<?= htmlspecialchars($__screenUrl($sectionScreenId)) ?>"
                       class="<?= $anyChildActive ? 'wp-has-current-submenu wp-menu-open menu-top' : 'wp-not-current-submenu menu-top' ?>">
                        <div class="wp-menu-image" data-icon="<?= htmlspecialchars($sectionIcon) ?>">
                            <span class="wp-menu-icon-placeholder"></span>
                        </div>
                        <div class="wp-menu-name"><?= htmlspecialchars($sectionLabel) ?></div>
                        <?php if ($sectionHasChildren): ?>
                        <div class="wp-submenu-arrow" aria-hidden="true">&#9662;</div>
                        <?php endif; ?>
                    </a>
                    <?php if ($sectionHasChildren): ?>
                    <ul class="wp-submenu wp-submenu-wrap">
                        <?php foreach ($sectionItems as $i => $item):
                            $childScreenId = $item['screenId'] ?? '';
                            $isChildActive = $childScreenId === $activeScreen;
                        ?>
                        <li class="<?= $isChildActive ? 'current' : '' ?>">
                            <a href="<?= htmlspecialchars($__screenUrl($childScreenId)) ?>"
                               class="<?= $isChildActive ? 'current' : '' ?>"
                               aria-current="<?= $isChildActive ? 'page' : 'false' ?>">
                                <?= htmlspecialchars($item['label'] ?? '') ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </li>
            <?php
                $sectionIndex++;
            endforeach;
            ?>
            </ul>
        </div>
    </div>

    <?php // ── Content Area ───────────────────────────────── ?>
    <div id="wpcontent">
        <div id="wpbody" role="main">
            <div id="wpbody-content">
                <?php // ── Screen Options ──────────────────── ?>
                <div id="screen-meta" class="metabox-prefs" style="display:none;">
                    <?php foreach ($screenOptions as $sopt):
                        if (($sopt['screenId'] ?? '') !== $activeScreen) continue; ?>
                    <div id="screen-options-wrap" class="hidden" tabindex="-1" aria-label="Screen Options Tab">
                        <form id="adv-settings" method="post">
                            <fieldset class="metabox-prefs">
                                <legend><?= htmlspecialchars($sopt['title'] ?? '') ?></legend>
                                <?php foreach ($sopt['options'] ?? [] as $opt): ?>
                                <label>
                                    <input type="<?= htmlspecialchars($opt['type'] ?? 'checkbox') ?>"
                                           name="<?= htmlspecialchars($opt['id'] ?? '') ?>"
                                           value="1" />
                                    <?= htmlspecialchars($opt['label'] ?? '') ?>
                                </label>
                                <?php endforeach; ?>
                            </fieldset>
                            <p class="submit"><input type="submit" class="button" value="Apply" /></p>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div id="screen-meta-links">
                    <div id="screen-options-link-wrap" class="hide-if-no-js screen-meta-toggle">
                        <button type="button" id="show-settings-link" class="button show-settings" aria-controls="screen-options-wrap" aria-expanded="false">Screen Options</button>
                    </div>
                </div>

                <?php // ── Page Content ────────────────────── ?>
                <div class="wrap">
                    <h1 class="wp-heading-inline"><?= htmlspecialchars($currentScreenTitle) ?></h1>
                    <hr class="wp-header-end" />

                    <?php if (!empty($content)): ?>
                        <?= $content ?>
                    <?php else: ?>
                        <div class="presto-content-area">
                            <?php
                            $__contentPath = __DIR__ . '/content.php';
                            if (file_exists($__contentPath)) {
                                include $__contentPath;
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

</div>

<?php // ── Footer scripts ────────────────────────────────── ?>
<script>
document.body.classList.remove('no-js');
document.getElementById('show-settings-link')?.addEventListener('click', function(e) {
    e.preventDefault();
    const meta = document.getElementById('screen-meta');
    const opts = document.getElementById('screen-options-wrap');
    if (meta) meta.style.display = meta.style.display === 'none' ? '' : 'none';
    if (opts) opts.classList.toggle('hidden');
});
</script>
</body>
</html>
