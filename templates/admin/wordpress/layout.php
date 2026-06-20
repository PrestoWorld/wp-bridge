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
?><!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> &lsaquo; PrestoWorld</title>

    <link rel="stylesheet" id="dashicons-css" href="https://s.w.org/wp-includes/css/dashicons.min.css?ver=6.7" media="all" />
    <link rel="stylesheet" id="wp-admin-css" href="https://s.w.org/wp-admin/css/wp-admin.min.css?ver=6.7" media="all" />
    <link rel="stylesheet" id="buttons-css" href="https://s.w.org/wp-includes/css/buttons.min.css?ver=6.7" media="all" />
    <link rel="stylesheet" id="forms-css" href="https://s.w.org/wp-admin/css/forms.min.css?ver=6.7" media="all" />
    <link rel="stylesheet" id="l10n-css" href="https://s.w.org/wp-admin/css/l10n.min.css?ver=6.7" media="all" />
    <link rel="stylesheet" id="list-tables-css" href="https://s.w.org/wp-admin/css/list-tables.min.css?ver=6.7" media="all" />
    <link rel="stylesheet" id="edit-css" href="https://s.w.org/wp-admin/css/edit.min.css?ver=6.7" media="all" />
    <link rel="stylesheet" id="admin-menu-css" href="https://s.w.org/wp-admin/css/admin-menu.min.css?ver=6.7" media="all" />

    <style>
        #wpcontent { padding-left: 0 !important; margin-left: 160px; }
        body { background: #f0f0f1; }
        .wrap { margin: 10px 20px 0 2px; }
        #admin-app-loading { display: none; }
        .presto-content-area { min-height: 400px; }
        .notice { margin: 5px 0 15px; }

        .screen-meta-toggle { position: absolute; top: 0; right: 0; }
        #screen-meta { z-index: 10; }
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
                    <span class="ab-icon <?= \PrestoWorld\Bridge\WordPress\Admin\Skins\WordPressSkin::iconClass($item['icon']) ?>"></span>
                    <?php endif; ?>
                    <?= htmlspecialchars($item['label'] ?? '') ?>
                </a>
                <?php elseif (($item['type'] ?? '') === 'notification'): ?>
                <a class="ab-item" href="#">
                    <span class="ab-icon <?= \PrestoWorld\Bridge\WordPress\Admin\Skins\WordPressSkin::iconClass($item['icon'] ?? 'Bell') ?>"></span>
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
                $sectionTitle = $section['title'] ?? '';

                if (count($sectionItems) === 1):
                    // Single item — render as flat top-level menu entry
                    $item = $sectionItems[0];
                    $screenId = $item['screenId'] ?? '';
                    $isActive = $screenId === $activeScreen;
                    ?>
                    <?php if ($sectionIndex > 0): ?>
                    <li class="wp-menu-separator" role="presentation"><div class="separator"></div></li>
                    <?php endif; ?>
                    <li class="menu-top menu-icon-<?= htmlspecialchars($screenId) ?> <?= $isActive ? 'wp-has-current-submenu wp-menu-open' : '' ?>">
                        <a href="<?= \PrestoWorld\Bridge\WordPress\Admin\Skins\WordPressSkin::screenUrl($screenId) ?>"
                           class="<?= $isActive ? 'wp-has-current-submenu wp-menu-open menu-top' : 'wp-not-current-submenu menu-top' ?>">
                            <div class="wp-menu-arrow"><div></div></div>
                            <div class="wp-menu-image dashicons-before <?= \PrestoWorld\Bridge\WordPress\Admin\Skins\WordPressSkin::iconClass($item['icon'] ?? '') ?>"><br></div>
                            <div class="wp-menu-name"><?= htmlspecialchars($item['label'] ?? '') ?></div>
                        </a>
                    </li>
                <?php else:
                    // Multiple items — render first as parent, rest as submenu
                    $firstItem = $sectionItems[0];
                    $firstScreenId = $firstItem['screenId'] ?? '';
                    $hasActiveChild = false;
                    foreach ($sectionItems as $si) {
                        if (($si['screenId'] ?? '') === $activeScreen) { $hasActiveChild = true; break; }
                    }
                    ?>
                    <?php if ($sectionIndex > 0): ?>
                    <li class="wp-menu-separator" role="presentation"><div class="separator"></div></li>
                    <?php endif; ?>
                    <li class="menu-top menu-icon-<?= htmlspecialchars($firstScreenId) ?> <?= $hasActiveChild ? 'wp-has-current-submenu wp-menu-open' : '' ?>">
                        <a href="<?= \PrestoWorld\Bridge\WordPress\Admin\Skins\WordPressSkin::screenUrl($firstScreenId) ?>"
                           class="<?= $hasActiveChild ? 'wp-has-current-submenu wp-menu-open menu-top' : 'wp-not-current-submenu menu-top' ?>">
                            <div class="wp-menu-arrow"><div></div></div>
                            <div class="wp-menu-image dashicons-before <?= \PrestoWorld\Bridge\WordPress\Admin\Skins\WordPressSkin::iconClass($firstItem['icon'] ?? '') ?>"><br></div>
                            <div class="wp-menu-name"><?= htmlspecialchars($firstItem['label'] ?? '') ?></div>
                        </a>
                        <div class="wp-submenu wp-submenu-wrap">
                            <div class="wp-submenu-head" aria-hidden="true"><?= htmlspecialchars($sectionTitle) ?></div>
                            <ul>
                            <?php foreach ($sectionItems as $si):
                                $siActive = ($si['screenId'] ?? '') === $activeScreen; ?>
                                <li class="<?= $siActive ? 'current' : '' ?>">
                                    <a href="<?= \PrestoWorld\Bridge\WordPress\Admin\Skins\WordPressSkin::screenUrl($si['screenId'] ?? '') ?>"
                                       class="<?= $siActive ? 'current' : '' ?>"
                                       aria-current="<?= $siActive ? 'page' : 'false' ?>">
                                        <?= htmlspecialchars($si['label'] ?? '') ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                            </ul>
                        </div>
                    </li>
                <?php endif; ?>
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
                            // content.php is included directly via PhpEngine
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
