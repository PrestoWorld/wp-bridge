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

use PrestoWorld\Bridge\WordPress\Admin\Dashicons;

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

$__assetBase = '/wp-admin/assets/css';
$__fontBase = '/wp-admin/assets/fonts';
$__cssFiles = [
    'dashicons.css',
    'admin-bar.css',
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
?>
<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> &lsaquo; PrestoWorld — WordPress</title>

    <meta name="presto-cdn-base" content="" />
    <!-- CDN assets will be served from presto-cdn in production -->

    <?php foreach ($__cssFiles as $__cssFile): ?>
    <link rel="stylesheet" id="wp-<?= htmlspecialchars(pathinfo($__cssFile, PATHINFO_FILENAME)) ?>-css" href="<?= $__assetBase ?>/<?= htmlspecialchars($__cssFile) ?>" type="text/css" media="all" />
    <?php endforeach; ?>
    <link rel="stylesheet" id="colors-css" href="<?= $__assetBase ?>/colors/blue/colors.css" type="text/css" media="all" />

    <style>
        /* PrestoWorld bridge overrides — WordPress 6.9+ design system */
        :root {
            --wp-admin-theme-color: #3858e9;
            --wp-admin-theme-color--rgb: 56, 88, 233;
            --wp-admin-theme-color-darker-10: #2145e6;
            --wp-admin-theme-color-darker-20: #183ad6;
            --wp-admin-border-width-focus: 1.5px;
        }

        #wpadminbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 99999;
        }
        body {
            padding-top: 32px;
        }
        .presto-content-area { min-height: 400px; }

        /* Menu icon rendering — ensure dashicons render properly in menu */
        #adminmenu div.wp-menu-image {
            float: left;
            width: 36px;
            height: 34px;
            margin: 0;
            text-align: center;
        }
        #adminmenu div.wp-menu-image svg,
        #adminmenu div.wp-menu-image img {
            display: none;
        }
        #adminmenu div.wp-menu-image .dashicons-before {
            display: inline-block;
            font-family: dashicons;
            font-size: 20px;
            line-height: 1;
            padding: 7px 0;
            color: #a7aaad;
            color: rgba(240, 246, 252, 0.6);
            transition: all .1s ease-in-out;
        }
        /* Hover state for menu icons */
        #adminmenu li:hover div.wp-menu-image .dashicons-before,
        #adminmenu li a:focus div.wp-menu-image .dashicons-before,
        #adminmenu li.opensub div.wp-menu-image .dashicons-before {
            color: var(--wp-admin-theme-color);
        }
        /* Active/current menu icon */
        #adminmenu li.wp-has-current-submenu:hover div.wp-menu-image .dashicons-before,
        #adminmenu .wp-has-current-submenu div.wp-menu-image .dashicons-before,
        #adminmenu .current div.wp-menu-image .dashicons-before,
        #adminmenu a.wp-has-current-submenu:hover div.wp-menu-image .dashicons-before,
        #adminmenu a.current:hover div.wp-menu-image .dashicons-before,
        #adminmenu li.wp-has-current-submenu a:focus div.wp-menu-image .dashicons-before,
        #adminmenu li.wp-has-current-submenu.opensub div.wp-menu-image .dashicons-before {
            color: #fff;
        }
        /* Admin bar icons */
        #wpadminbar .ab-icon.dashicons-before {
            display: inline-block;
            font-family: dashicons;
            font-size: 20px;
            line-height: 1;
            width: 20px;
            height: 20px;
            text-align: center;
        }
        /* Collapse button icon */
        #collapse-button .collapse-button-icon {
            display: inline-block;
            font-family: dashicons;
            font-size: 20px;
            line-height: 1;
        }
        #collapse-button .collapse-button-icon:before {
            content: "\f148";
        }
        .folded #collapse-button .collapse-button-icon:before {
            content: "\f140";
        }

        /* Submenu flyout behavior */
        #adminmenu li.wp-not-current-submenu:hover .wp-submenu,
        #adminmenu li.wp-not-current-submenu:focus-within .wp-submenu,
        #adminmenu li.opensub .wp-submenu {
            top: -1px;
            display: block;
        }

        /* Ensure submenu is hidden by default for non-current items */
        #adminmenu li.wp-not-current-submenu .wp-submenu {
            display: none;
            top: -1000em;
        }

        /* Flyout arrow for submenu */
        #adminmenu li.wp-has-submenu.wp-not-current-submenu:hover:after,
        #adminmenu li.wp-has-submenu.wp-not-current-submenu:focus-within:after {
            right: 0;
            border: 8px solid transparent;
            content: " ";
            height: 0;
            width: 0;
            position: absolute;
            pointer-events: none;
            top: 10px;
            z-index: 10000;
            border-right-color: #2c3338;
        }

        /* Menu item hover background */
        #adminmenu li.menu-top:hover,
        #adminmenu li.opensub > a.menu-top,
        #adminmenu li > a.menu-top:focus {
            position: relative;
            background-color: #1d2327;
            color: var(--wp-admin-theme-color);
        }

        /* Current menu item */
        #adminmenu li.wp-has-current-submenu a.wp-has-current-submenu,
        #adminmenu li.current a.menu-top,
        #adminmenu .wp-has-current-submenu .wp-submenu .wp-submenu-head {
            background: var(--wp-admin-theme-color);
            color: #fff;
        }

        /* Submenu item hover */
        #adminmenu .wp-submenu a:hover,
        #adminmenu .wp-submenu a:focus {
            color: var(--wp-admin-theme-color);
            box-shadow: inset 4px 0 0 0 currentColor;
            transition: box-shadow .1s linear;
        }

        /* Submenu current item */
        #adminmenu .wp-submenu li.current,
        #adminmenu .wp-submenu li.current a,
        #adminmenu .opensub .wp-submenu li.current a,
        #adminmenu a.wp-has-current-submenu:focus + .wp-submenu li.current a,
        #adminmenu .wp-submenu li.current a:hover,
        #adminmenu .wp-submenu li.current a:focus {
            color: #fff;
        }
    </style>

    <script>
    window.__INITIAL_STATE__ = <?= json_encode($initialState, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>
</head>
<body class="wp-admin wp-core-ui no-js multisite admin-color-blue locale-en-us branch-6-9 version-6-9 php-js svg admin-bar no-customize-support screen-<?= htmlspecialchars($activeScreen) ?>">
<script>
    document.body.className = document.body.className.replace('no-js','js');
</script>

<?php
// ── Admin Bar ──────────────────────────────────────────────
$adminBarItems = $adminBar['items'] ?? [];
?>
<div id="wpadminbar" class="nojq">
    <div class="quicklinks" id="wp-toolbar" role="navigation" aria-label="Toolbar">
        <ul id="wp-admin-bar-root-default" class="ab-top-menu">
            <li id="wp-admin-bar-wp-logo" class="menupop">
                <a class="ab-item" aria-haspopup="true" href="/" tabindex="0">
                    <span class="ab-icon dashicons-before dashicons-wordpress" aria-hidden="true"></span>
                    <span class="screen-reader-text">About PrestoWorld</span>
                </a>
            </li>
            <li id="wp-admin-bar-site-name" class="menupop">
                <a class="ab-item" aria-haspopup="true" href="/">PrestoWorld</a>
            </li>
            <li id="wp-admin-bar-comments" class="menupop">
                <a class="ab-item" aria-haspopup="true" href="/wp-admin/edit-comments.php">
                    <span class="ab-icon dashicons-before dashicons-admin-comments" aria-hidden="true"></span>
                    <span id="ab-awaiting-mod" class="ab-label awaiting-mod pending-count count-0" aria-hidden="true">0</span>
                </a>
            </li>
            <li id="wp-admin-bar-new-content" class="menupop">
                <a class="ab-item" aria-haspopup="true" href="/wp-admin/post-new.php">
                    <span class="ab-icon dashicons-before dashicons-plus-alt" aria-hidden="true"></span>
                    <span class="ab-label">New</span>
                </a>
            </li>
        </ul>
        <ul id="wp-admin-bar-top-secondary" class="ab-top-menu">
            <?php foreach ($adminBarItems as $item): ?>
                <li id="wp-admin-bar-<?= htmlspecialchars($item['id'] ?? '') ?>">
                <?php if (($item['type'] ?? '') === 'link'): ?>
                <a class="ab-item" href="<?= htmlspecialchars($item['href'] ?? '#') ?>">
                    <?php if (!empty($item['icon'])): ?>
                    <?= Dashicons::adminBarIcon($item['icon']) ?>
                    <?php endif; ?>
                    <?= htmlspecialchars($item['label'] ?? '') ?>
                </a>
                <?php elseif (($item['type'] ?? '') === 'notification'): ?>
                <a class="ab-item" href="#">
                    <?= Dashicons::adminBarIcon($item['icon'] ?? 'Bell') ?>
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
                <a class="ab-item" aria-haspopup="true" href="/wp-admin/profile.php">
                    Howdy, <?= htmlspecialchars($user['name'] ?? 'Admin') ?>
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
        <a href="#wpbody-content" class="screen-reader-shortcut">Skip to main content</a>
        <a href="#wp-toolbar" class="screen-reader-shortcut">Skip to toolbar</a>
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
                <li class="menu-top menu-icon-<?= htmlspecialchars($sectionScreenId) ?> <?= $anyChildActive ? 'wp-has-current-submenu wp-menu-open' : 'wp-not-current-submenu' ?>">
                    <a href="<?= htmlspecialchars($__screenUrl($sectionScreenId)) ?>"
                       class="<?= $anyChildActive ? 'wp-has-current-submenu wp-menu-open menu-top' : 'wp-not-current-submenu menu-top' ?>"
                       <?= $sectionHasChildren ? 'aria-haspopup="true"' : '' ?>>
                        <?= Dashicons::menuImage($sectionIcon) ?>
                        <div class="wp-menu-name"><?= htmlspecialchars($sectionLabel) ?></div>
                    </a>
                    <?php if ($sectionHasChildren): ?>
                    <ul class="wp-submenu wp-submenu-wrap">
                        <li class="wp-submenu-head" aria-hidden="true"><?= htmlspecialchars($sectionLabel) ?></li>
                        <?php foreach ($sectionItems as $i => $item):
                            $childScreenId = $item['screenId'] ?? '';
                            $isChildActive = $childScreenId === $activeScreen;
                        ?>
                        <li class="<?= $i === 0 ? 'wp-first-item ' : '' ?><?= $isChildActive ? 'current' : '' ?>">
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
            <li id="collapse-menu" class="hide-if-no-js">
                <button type="button" id="collapse-button" aria-label="Collapse Main menu" aria-expanded="true">
                    <span class="collapse-button-icon" aria-hidden="true"></span>
                    <span class="collapse-button-label">Collapse Menu</span>
                </button>
            </li>
            </ul>
        </div>
    </div>

    <div id="wpcontent">

        <div id="wpbody" role="main">

            <div id="wpbody-content">
                <?php // ── Screen Options ──────────────────── ?>
                <div id="screen-meta-links">
                    <div id="screen-options-link-wrap" class="hide-if-no-js screen-meta-toggle">
                        <button type="button" id="show-settings-link" class="button show-settings" aria-controls="screen-options-wrap" aria-expanded="false">Screen Options</button>
                    </div>
                </div>
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

                <div class="clear"></div>
            </div><!-- wpbody-content -->

            <div class="clear"></div>
        </div><!-- wpbody -->

        <div class="clear"></div>
    </div><!-- wpcontent -->

    <div id="wpfooter" role="contentinfo">
        <p id="footer-left" class="alignleft">
            <span id="footer-thankyou">Thank you for creating with <a href="https://prestoworld.org/">PrestoWorld</a>.</span>
        </p>
        <p id="footer-upgrade" class="alignright">
            <strong>PrestoWorld</strong>
        </p>
        <div class="clear"></div>
    </div>

    <div class="clear"></div>
</div><!-- wpwrap -->

<?php // ── Footer scripts ────────────────────────────────── ?>
<script>
document.getElementById('show-settings-link')?.addEventListener('click', function(e) {
    e.preventDefault();
    var meta = document.getElementById('screen-meta');
    var opts = document.getElementById('screen-options-wrap');
    if (meta) meta.style.display = meta.style.display === 'none' ? '' : 'none';
    if (opts) opts.classList.toggle('hidden');
});
document.getElementById('collapse-button')?.addEventListener('click', function() {
    document.body.classList.toggle('folded');
    var expanded = this.getAttribute('aria-expanded') === 'true';
    this.setAttribute('aria-expanded', String(!expanded));
});

// Menu hover behavior — submenu flyout
(function() {
    var menuItems = document.querySelectorAll('#adminmenu li.menu-top');
    menuItems.forEach(function(item) {
        var link = item.querySelector('a.menu-top');
        var submenu = item.querySelector('.wp-submenu');
        if (!link || !submenu) return;

        // Add hover class for CSS flyout
        item.addEventListener('mouseenter', function() {
            if (item.classList.contains('wp-not-current-submenu')) {
                item.classList.add('opensub');
            }
        });
        item.addEventListener('mouseleave', function() {
            if (item.classList.contains('wp-not-current-submenu')) {
                item.classList.remove('opensub');
            }
        });

        // Focus behavior
        link.addEventListener('focus', function() {
            if (item.classList.contains('wp-not-current-submenu')) {
                item.classList.add('opensub');
            }
        });
        link.addEventListener('blur', function() {
            if (item.classList.contains('wp-not-current-submenu')) {
                item.classList.remove('opensub');
            }
        });
    });
})();
</script>
</body>
</html>
