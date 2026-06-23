<?php
/**
 * @var string $activeScreen
 * @var array  $widgets
 * @var array  $initialState
 * @var array  $user
 */
?>
<div id="poststuff">
    <div id="post-body" class="metabox-holder columns-2">

        <?php if ($activeScreen === 'dashboard'): ?>

            <div id="post-body-content">
                <div id="dashboard-widgets-wrap">
                    <div id="dashboard-widgets" class="metabox-holder">
                        <?php
                        $cols = [1 => [], 2 => []];
                        foreach ($widgets as $w) {
                            $col = $w['props']['column'] ?? 1;
                            $cols[$col][] = $w;
                        }
                        foreach ([1, 2] as $colIdx):
                        ?>
                        <div class="postbox-container" style="width:49%;<?= $colIdx === 2 ? 'float:right;' : '' ?>">
                            <?php foreach ($cols[$colIdx] as $widget):
                                $widgetId = $widget['id'] ?? '';
                                $widgetTitle = $widget['title'] ?? '';
                                $widgetContent = $widget['props']['content'] ?? '';
                            ?>
                            <div class="postbox" id="<?= htmlspecialchars($widgetId) ?>">
                                <div class="postbox-header">
                                    <h2 class="hndle ui-sortable-handle">
                                        <span><?= htmlspecialchars($widgetTitle) ?></span>
                                    </h2>
                                </div>
                                <div class="inside">
                                    <?= $widgetContent ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div id="postbox-container-1" class="postbox-container side">
                <div class="postbox" id="dashboard-right-now">
                    <div class="postbox-header">
                        <h2 class="hndle ui-sortable-handle"><span>At a Glance</span></h2>
                    </div>
                    <div class="inside">
                        <div class="main">
                            <?php
                            $db = app(\Cycle\Database\DatabaseInterface::class);
                            $pfx = getenv('PW_TABLE_PREFIX') ?: 'wp_';
                            $postCount = (int) ($db->select('COUNT(*) as c')->from($pfx . 'posts')->where('post_status', '!=', 'auto-draft')->fetch()['c'] ?? 0);
                            $userCount = (int) ($db->select('COUNT(*) as c')->from($pfx . 'users')->fetch()['c'] ?? 0);
                            $commentCount = (int) ($db->select('COUNT(*) as c')->from($pfx . 'comments')->fetch()['c'] ?? 0);
                            ?>
                            <ul style="list-style:none;margin:0;padding:0;">
                                <li style="padding:4px 0;"><strong><?= $postCount ?></strong> Posts</li>
                                <li style="padding:4px 0;"><strong><?= $userCount ?></strong> Users</li>
                                <li style="padding:4px 0;"><strong><?= $commentCount ?></strong> Comments</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($activeScreen === 'posts'): ?>

            <?php
            $db = app(\Cycle\Database\DatabaseInterface::class);
            $pfx = getenv('PW_TABLE_PREFIX') ?: 'wp_';
            $posts = $db->select('*')->from($pfx . 'posts')->where('post_type', 'post')->where('post_status', '!=', 'auto-draft')->orderBy('post_date', 'DESC')->fetchAll();
            $totalPosts = count($posts);
            ?>
            <div id="post-body-content">
                <div class="tablenav top">
                    <div class="alignleft actions">
                        <select name="cat" id="cat" class="postform"><option value="0">All categories</option></select>
                        <input type="submit" class="button" value="Filter" />
                    </div>
                    <div class="tablenav-pages one-page"><span class="displaying-num"><?= $totalPosts ?> items</span></div>
                    <br class="clear" />
                </div>
                <table class="wp-list-table widefat fixed striped posts">
                    <thead><tr>
                        <td class="manage-column column-cb check-column"><input id="cb-select-all-1" type="checkbox" /></td>
                        <th class="manage-column column-title column-primary">Title</th>
                        <th class="manage-column column-author">Author</th>
                        <th class="manage-column column-categories">Categories</th>
                        <th class="manage-column column-date">Date</th>
                    </tr></thead>
                    <tbody id="the-list">
                        <?php if (empty($posts)): ?>
                        <tr class="no-items"><td class="colspanchange" colspan="5">No posts found.</td></tr>
                        <?php else: ?>
                        <?php foreach ($posts as $p): $pid = $p['ID'] ?? $p['id']; ?>
                        <tr>
                            <th class="check-column"><input type="checkbox" /></th>
                            <td class="title column-title has-row-actions column-primary">
                                <strong><a href="<?= htmlspecialchars($__screenUrl('post')) ?>?post=<?= $pid ?>"><?= htmlspecialchars($p['post_title'] ?? '') ?></a></strong>
                                <div class="row-actions">
                                    <span class="edit"><a href="<?= htmlspecialchars($__screenUrl('post')) ?>?post=<?= $pid ?>&action=edit">Edit</a></span>
                                    <span class="trash"><a href="#">Trash</a></span>
                                    <span class="view"><a href="/?p=<?= $pid ?>">View</a></span>
                                </div>
                            </td>
                            <td class="author column-author">admin</td>
                            <td class="categories column-categories">Uncategorized</td>
                            <td class="date column-date"><?= htmlspecialchars(date('Y-m-d', strtotime($p['post_date'] ?? ''))) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($activeScreen === 'post-new'): ?>

            <div id="post-body-content">
                <div class="notice notice-info inline">
                    <p>Add New Post — data from <code>pw_posts</code> will save here.</p>
                </div>
                <div style="background:#fff;border:1px solid #dcdcde;padding:20px;">
                    <div style="margin-bottom:16px;">
                        <label style="display:block;font-weight:600;margin-bottom:4px;">Title</label>
                        <input type="text" style="width:100%;padding:8px;font-size:16px;border:1px solid #8c8f94;border-radius:4px;" placeholder="Add title" />
                    </div>
                    <div style="margin-bottom:16px;">
                        <label style="display:block;font-weight:600;margin-bottom:4px;">Content</label>
                        <textarea rows="12" style="width:100%;padding:8px;border:1px solid #8c8f94;border-radius:4px;font-family:monospace;"></textarea>
                    </div>
                    <p class="submit"><input type="submit" class="button button-primary" value="Publish" /></p>
                </div>
            </div>

        <?php elseif ($activeScreen === 'upload'): ?>

            <?php
            $db = app(\Cycle\Database\DatabaseInterface::class);
            $pfx = getenv('PW_TABLE_PREFIX') ?: 'wp_';
            $basePath = getcwd();
            $contentDir = getenv('PW_CONTENT_DIR') ?: ($basePath . '/public/wp-content');

            try {
                $mediaRows = $db->select('*')
                    ->from($pfx . 'posts')
                    ->where('post_type', 'attachment')
                    ->where('post_status', '!=', 'auto-draft')
                    ->orderBy('post_date', 'DESC')
                    ->limit(50)
                    ->fetchAll();
            } catch (\Throwable) {
                $mediaRows = [];
            }

            $mediaItems = [];
            foreach ($mediaRows as $row) {
                $id = (int) ($row['ID'] ?? $row['id']);
                $attachedFile = null;
                try {
                    $m = $db->select('meta_value')->from($pfx . 'postmeta')
                        ->where('post_id', $id)->where('meta_key', '_wp_attached_file')->limit(1)->fetch();
                    $attachedFile = $m['meta_value'] ?? null;
                } catch (\Throwable) {}

                $subPath = $attachedFile ?? '';
                $fileName = basename($subPath ?: '');
                $mime = $row['post_mime_type'] ?? '';

                $localUrl = '';
                $isImage = str_starts_with($mime, 'image/');

                if ($subPath && file_exists($basePath . '/storage/uploads/' . $subPath)) {
                    $localUrl = '/storage/uploads/' . $subPath;
                } elseif ($subPath && file_exists($contentDir . '/uploads/' . $subPath)) {
                    $localUrl = '/wp-content/uploads/' . $subPath;
                }

                $authorName = 'Unknown';
                $authorId = (int) ($row['post_author'] ?? 0);
                if ($authorId > 0) {
                    try {
                        $u = $db->select('display_name')->from($pfx . 'users')
                            ->where('ID', $authorId)->limit(1)->fetch();
                        $authorName = $u['display_name'] ?? 'Unknown';
                    } catch (\Throwable) {}
                }

                $mediaItems[] = [
                    'id' => $id,
                    'title' => $row['post_title'] ?: $fileName ?: "(no title)",
                    'file' => $fileName,
                    'url' => $localUrl,
                    'mime' => $mime,
                    'isImage' => $isImage,
                    'author' => $authorName,
                    'date' => date('Y-m-d H:i', strtotime($row['post_date'] ?? '')),
                ];
            }

            // Also scan Presto storage for local files not in WP DB
            $storageDir = $basePath . '/storage/uploads';
            if (is_dir($storageDir)) {
                $iter = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($storageDir, RecursiveDirectoryIterator::SKIP_DOTS)
                );
                foreach ($iter as $f) {
                    if ($f->isDir() || $f->getFilename()[0] === '.') continue;
                    $relative = str_replace($storageDir . '/', '', $f->getPathname());
                    $dup = false;
                    foreach ($mediaItems as $mi) {
                        if ($mi['file'] === $f->getFilename()) { $dup = true; break; }
                    }
                    if ($dup) continue;
                    $mime = mime_content_type($f->getPathname()) ?: 'application/octet-stream';
                    $mediaItems[] = [
                        'id' => 0,
                        'title' => $f->getFilename(),
                        'file' => $f->getFilename(),
                        'url' => '/storage/uploads/' . $relative,
                        'mime' => $mime,
                        'isImage' => str_starts_with($mime, 'image/'),
                        'author' => 'Presto',
                        'date' => date('Y-m-d H:i', $f->getMTime()),
                    ];
                }
            }
            ?>

            <div id="post-body-content">
                <div class="tablenav top">
                    <div class="alignleft actions bulkactions">
                        <select><option value="">Bulk actions</option></select>
                        <input type="submit" class="button" value="Apply" />
                    </div>
                    <div class="alignleft actions">
                        <select><option value="">All media items</option></select>
                        <input type="submit" class="button" value="Filter" />
                    </div>
                    <div class="tablenav-pages">
                        <span class="displaying-num"><?= count($mediaItems) ?> items</span>
                    </div>
                    <br class="clear" />
                </div>
                <table class="wp-list-table widefat fixed striped media">
                    <thead><tr>
                        <td class="manage-column column-cb check-column"><input type="checkbox" /></td>
                        <th class="manage-column column-icon">File</th>
                        <th class="manage-column column-title column-primary">Title</th>
                        <th class="manage-column column-author">Author</th>
                        <th class="manage-column column-date">Date</th>
                    </tr></thead>
                    <tbody id="the-list">
                        <?php if (empty($mediaItems)): ?>
                        <tr class="no-items"><td class="colspanchange" colspan="5">No media items found.</td></tr>
                        <?php else: ?>
                        <?php foreach ($mediaItems as $mi): ?>
                        <tr>
                            <th class="check-column"><input type="checkbox" /></th>
                            <td class="column-icon">
                                <?php if ($mi['isImage'] && $mi['url']): ?>
                                <img src="<?= htmlspecialchars($mi['url']) ?>" alt="" style="width:60px;height:60px;object-fit:cover;border-radius:4px;" />
                                <?php else: ?>
                                <div style="width:60px;height:60px;background:#f0f0f1;border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:24px;color:#999;"><?= str_starts_with($mi['mime'], 'video/') ? '🎬' : (str_starts_with($mi['mime'], 'audio/') ? '🎵' : '📄') ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="column-title column-primary">
                                <strong><?= htmlspecialchars($mi['title']) ?></strong>
                                <div class="row-actions">
                                    <span class="view"><a href="<?= htmlspecialchars($mi['url']) ?>" target="_blank">View</a></span>
                                </div>
                            </td>
                            <td class="column-author"><?= htmlspecialchars($mi['author']) ?></td>
                            <td class="column-date"><?= htmlspecialchars($mi['date']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot><tr>
                        <td class="manage-column column-cb check-column"><input type="checkbox" /></td>
                        <th class="manage-column column-icon">File</th>
                        <th class="manage-column column-title column-primary">Title</th>
                        <th class="manage-column column-author">Author</th>
                        <th class="manage-column column-date">Date</th>
                    </tr></tfoot>
                </table>
                <div class="tablenav bottom">
                    <div class="alignleft actions bulkactions">
                        <select><option value="">Bulk actions</option></select>
                        <input type="submit" class="button" value="Apply" />
                    </div>
                    <div class="tablenav-pages">
                        <span class="displaying-num"><?= count($mediaItems) ?> items</span>
                    </div>
                    <br class="clear" />
                </div>
            </div>

        <?php elseif ($activeScreen === 'edit-pages'): ?>

            <?php
            $db = app(\Cycle\Database\DatabaseInterface::class);
            $pfx = getenv('PW_TABLE_PREFIX') ?: 'wp_';
            $pages = $db->select('*')->from($pfx . 'posts')->where('post_type', 'page')->where('post_status', '!=', 'auto-draft')->orderBy('post_date', 'DESC')->fetchAll();
            ?>
            <div id="post-body-content">
                <table class="wp-list-table widefat fixed striped pages">
                    <thead><tr>
                        <td class="manage-column column-cb check-column"><input type="checkbox" /></td>
                        <th class="manage-column column-title column-primary">Title</th>
                        <th class="manage-column column-author">Author</th>
                        <th class="manage-column column-date">Date</th>
                    </tr></thead>
                    <tbody id="the-list">
                        <?php if (empty($pages)): ?>
                        <tr class="no-items"><td class="colspanchange" colspan="4">No pages found.</td></tr>
                        <?php else: ?>
                        <?php foreach ($pages as $p): $pid = $p['ID'] ?? $p['id']; ?>
                        <tr>
                            <th class="check-column"><input type="checkbox" /></th>
                            <td class="title column-title has-row-actions column-primary">
                                <strong><a href="<?= htmlspecialchars($__screenUrl('post')) ?>?post=<?= $pid ?>&action=edit"><?= htmlspecialchars($p['post_title'] ?? '') ?></a></strong>
                                <div class="row-actions">
                                    <span class="edit"><a href="<?= htmlspecialchars($__screenUrl('post')) ?>?post=<?= $pid ?>&action=edit">Edit</a></span>
                                    <span class="view"><a href="/?page_id=<?= $pid ?>">View</a></span>
                                </div>
                            </td>
                            <td class="author column-author">admin</td>
                            <td class="date column-date"><?= htmlspecialchars(date('Y-m-d', strtotime($p['post_date'] ?? ''))) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($activeScreen === 'edit-comments'): ?>

            <?php
            $db = app(\Cycle\Database\DatabaseInterface::class);
            $pfx = getenv('PW_TABLE_PREFIX') ?: 'wp_';
            $comments = $db->select('*')->from($pfx . 'comments')->orderBy('comment_date', 'DESC')->fetchAll();
            ?>
            <div id="post-body-content">
                <table class="wp-list-table widefat fixed striped comments">
                    <thead><tr>
                        <td class="manage-column column-cb check-column"><input type="checkbox" /></td>
                        <th class="manage-column column-author column-primary">Author</th>
                        <th class="manage-column column-comment">Comment</th>
                        <th class="manage-column column-response">In Response To</th>
                        <th class="manage-column column-date">Submitted On</th>
                    </tr></thead>
                    <tbody id="the-list">
                        <?php if (empty($comments)): ?>
                        <tr class="no-items"><td class="colspanchange" colspan="5">No comments found.</td></tr>
                        <?php else: ?>
                        <?php foreach ($comments as $c): $cid = $c['comment_ID'] ?? $c['id']; ?>
                        <tr>
                            <th class="check-column"><input type="checkbox" /></th>
                            <td class="author column-author"><?= htmlspecialchars($c['comment_author'] ?? '') ?><br /><?= htmlspecialchars($c['comment_author_email'] ?? '') ?></td>
                            <td class="comment column-comment"><?= htmlspecialchars(substr($c['comment_content'] ?? '', 0, 100)) ?></td>
                            <td class="response column-response">Post #<?= (int) ($c['comment_post_ID'] ?? 0) ?></td>
                            <td class="date column-date"><?= htmlspecialchars(date('Y-m-d', strtotime($c['comment_date'] ?? ''))) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($activeScreen === 'themes'): ?>

            <div id="post-body-content">
                <?php
                try {
                    $themesDir = getenv('PW_CONTENT_DIR')
                        ? getenv('PW_CONTENT_DIR') . '/themes'
                        : null;
                    $repo = new \PrestoWorld\Theme\ThemeRepository($themesDir);
                    $themes = $repo->getAll();
                    $activeTheme = $repo->getActive();
                } catch (\Throwable $e) {
                    $themes = [];
                    $activeTheme = null;
                }
                ?>
                <div class="notice notice-info inline">
                    <p>Manage themes for your site. The active theme is highlighted.</p>
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:24px;margin-top:16px;">
                    <?php foreach ($themes as $theme):
                        $isActive = ($theme['directory'] ?? '') === $activeTheme;
                        $screenshot = $theme['screenshot'] ?? null;
                    ?>
                    <div style="width:300px;background:#fff;border:<?= $isActive ? '2px solid #2271b1' : '1px solid #dcdcde' ?>;border-radius:4px;overflow:hidden;position:relative;">
                        <?php if ($isActive): ?>
                        <div style="position:absolute;top:8px;left:8px;background:#2271b1;color:#fff;font-size:10px;font-weight:700;padding:2px 8px;border-radius:3px;z-index:1;">Active</div>
                        <?php endif; ?>
                        <div style="background:#f0f0f1;aspect-ratio:4/3;display:flex;align-items:center;justify-content:center;">
                            <?php if ($screenshot): ?>
                            <img src="<?= htmlspecialchars($screenshot) ?>" alt="<?= htmlspecialchars($theme['name'] ?? '') ?>" style="width:100%;height:100%;object-fit:cover;" loading="lazy" />
                            <?php else: ?>
                            <span style="color:#c3c4c7;font-size:32px;">--</span>
                            <?php endif; ?>
                        </div>
                        <div style="padding:12px 16px;">
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:4px;">
                                <h3 style="margin:0;font-size:14px;font-weight:600;"><?= htmlspecialchars($theme['name'] ?? '') ?></h3>
                                <?php if (!empty($theme['version'])): ?>
                                <span style="font-size:10px;color:#787c82;font-family:monospace;">v<?= htmlspecialchars($theme['version']) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($theme['author'])): ?>
                            <p style="margin:0 0 6px;font-size:12px;color:#646970;">By <?= htmlspecialchars($theme['author']) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($theme['description'])): ?>
                            <p style="margin:0 0 8px;font-size:12px;color:#50575e;line-height:1.4;"><?= htmlspecialchars($theme['description']) ?></p>
                            <?php endif; ?>
                            <?php if (!$isActive): ?>
                            <form method="post" action="" style="margin-top:8px;padding-top:8px;border-top:1px solid #f0f0f1;">
                                <input type="hidden" name="action" value="activate-theme" />
                                <input type="hidden" name="theme" value="<?= htmlspecialchars($theme['directory'] ?? '') ?>" />
                                <button type="submit" class="button button-primary" style="font-size:12px;padding:4px 16px;">Activate</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (empty($themes)): ?>
                <p style="color:#787c82;font-size:13px;margin-top:16px;">No themes installed.</p>
                <?php endif; ?>
            </div>

        <?php elseif ($activeScreen === 'widgets'): ?>

            <div id="post-body-content">
                <div class="notice notice-info inline">
                    <p>Widget management — drag and drop widgets into sidebars. Widget data will be stored via the API.</p>
                </div>
                <div style="display:flex;gap:24px;">
                    <div style="flex:1;">
                        <h2 style="font-size:14px;font-weight:600;">Available Widgets</h2>
                        <div style="background:#fff;border:1px solid #dcdcde;padding:12px;">
                            <div style="padding:8px;background:#f6f7f7;border:1px solid #dcdcde;margin-bottom:8px;cursor:move;">Recent Posts</div>
                            <div style="padding:8px;background:#f6f7f7;border:1px solid #dcdcde;margin-bottom:8px;cursor:move;">Recent Comments</div>
                            <div style="padding:8px;background:#f6f7f7;border:1px solid #dcdcde;margin-bottom:8px;cursor:move;">Search</div>
                            <div style="padding:8px;background:#f6f7f7;border:1px solid #dcdcde;margin-bottom:8px;cursor:move;">Archives</div>
                            <div style="padding:8px;background:#f6f7f7;border:1px solid #dcdcde;cursor:move;">Meta</div>
                        </div>
                    </div>
                    <div style="flex:1;">
                        <h2 style="font-size:14px;font-weight:600;">Sidebar</h2>
                        <div style="background:#fff;border:1px solid #dcdcde;min-height:200px;padding:12px;">
                            <p style="color:#787c82;">No widgets assigned yet.</p>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($activeScreen === 'nav-menus'): ?>

            <div id="post-body-content">
                <div class="notice notice-info inline">
                    <p>Menu management — create and manage navigation menus. Menu data will be stored via the API.</p>
                </div>
                <div style="background:#fff;border:1px solid #dcdcde;padding:20px;">
                    <div style="display:flex;gap:24px;">
                        <div style="flex:1;">
                            <h2 style="font-size:14px;font-weight:600;">Menu Structure</h2>
                            <div style="border:1px solid #dcdcde;min-height:200px;padding:12px;background:#f6f7f7;">
                                <p style="color:#787c82;">No menu items yet. Add pages, posts, or custom links.</p>
                            </div>
                        </div>
                        <div style="width:280px;">
                            <h2 style="font-size:14px;font-weight:600;">Add Menu Items</h2>
                            <div style="border:1px solid #dcdcde;padding:12px;margin-bottom:8px;background:#fff;">
                                <label style="font-weight:600;font-size:12px;">Pages</label>
                                <div style="margin-top:4px;"><button class="button">View All</button></div>
                            </div>
                            <div style="border:1px solid #dcdcde;padding:12px;background:#fff;">
                                <label style="font-weight:600;font-size:12px;">Custom Links</label>
                                <div style="margin-top:4px;">
                                    <input type="text" placeholder="URL" style="width:100%;margin-bottom:4px;" />
                                    <input type="text" placeholder="Link Text" style="width:100%;" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($activeScreen === 'plugins'): ?>

            <div id="post-body-content">
                <div class="notice notice-info inline">
                    <p>Plugin management is available through the API. Data from <code>plugin_registry</code> will render here.</p>
                </div>
                <table class="wp-list-table widefat fixed striped plugins">
                    <thead><tr>
                        <td class="manage-column column-cb check-column"><input id="cb-select-all-1" type="checkbox" /></td>
                        <th class="manage-column column-name column-primary">Plugin</th>
                        <th class="manage-column column-description">Description</th>
                        <th class="manage-column column-status">Status</th>
                        <th class="manage-column column-version">Version</th>
                    </tr></thead>
                    <tbody id="the-list">
                        <tr class="no-items"><td class="colspanchange" colspan="5">No plugins installed.</td></tr>
                    </tbody>
                </table>
            </div>

        <?php elseif ($activeScreen === 'plugin-install'): ?>

            <div id="post-body-content">
                <div class="notice notice-info inline">
                    <p>Install plugins from the WordPress.org repository or upload a .zip file.</p>
                </div>
                <div style="background:#fff;border:1px solid #dcdcde;padding:20px;">
                    <input type="search" placeholder="Search plugins..." style="width:60%;padding:8px;border:1px solid #8c8f94;border-radius:4px;" />
                    <input type="submit" class="button" value="Search" style="margin-left:4px;" />
                </div>
                <div style="margin-top:16px;background:#fff;border:1px solid #dcdcde;padding:20px;">
                    <h2 style="font-size:14px;font-weight:600;margin:0 0 8px;">Or upload a .zip file</h2>
                    <input type="file" accept=".zip" />
                    <input type="submit" class="button" value="Install Now" style="margin-left:4px;" />
                </div>
            </div>


        <?php elseif ($activeScreen === 'users'): ?>

            <?php
            $db = app(\Cycle\Database\DatabaseInterface::class);
            $pfx = getenv('PW_TABLE_PREFIX') ?: 'wp_';
            $users = $db->select('*')->from($pfx . 'users')->orderBy('user_registered', 'DESC')->fetchAll();
            ?>
            <div id="post-body-content">
                <table class="wp-list-table widefat fixed striped users">
                    <thead><tr>
                        <td class="manage-column column-cb check-column"><input type="checkbox" /></td>
                        <th class="manage-column column-username column-primary">Username</th>
                        <th class="manage-column column-name">Name</th>
                        <th class="manage-column column-email">Email</th>
                        <th class="manage-column column-role">Role</th>
                        <th class="manage-column column-date">Registered</th>
                    </tr></thead>
                    <tbody id="the-list">
                        <?php if (empty($users)): ?>
                        <tr class="no-items"><td class="colspanchange" colspan="6">No users found.</td></tr>
                        <?php else: ?>
                        <?php foreach ($users as $u): $uid = $u['ID'] ?? $u['id']; ?>
                        <tr>
                            <th class="check-column"><input type="checkbox" /></th>
                            <td class="username column-username"><strong><?= htmlspecialchars($u['user_login'] ?? '') ?></strong></td>
                            <td class="name column-name"><?= htmlspecialchars($u['display_name'] ?? $u['user_nicename'] ?? '') ?></td>
                            <td class="email column-email"><?= htmlspecialchars($u['user_email'] ?? '') ?></td>
                            <td class="role column-role"><?php
                            try {
                                $caps = $db->select('meta_value')->from($pfx . 'usermeta')->where('user_id', $uid)->where('meta_key', 'wp_capabilities')->limit(1)->fetch();
                                if ($caps) {
                                    $unser = unserialize($caps['meta_value']);
                                    if (is_array($unser)) echo htmlspecialchars(implode(', ', array_keys($unser)));
                                    else echo 'subscriber';
                                } else echo 'subscriber';
                            } catch (\Throwable) { echo 'subscriber'; }
                            ?></td>
                            <td class="date column-date"><?= htmlspecialchars(date('Y-m-d', strtotime($u['user_registered'] ?? ''))) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($activeScreen === 'profile'): ?>


            <div id="post-body-content">
                <div class="notice notice-info inline">
                    <p>Edit your profile — data from <code>pw_users</code> will save here.</p>
                </div>
                <div style="background:#fff;border:1px solid #dcdcde;padding:20px;">
                    <table class="form-table" role="presentation">
                        <tr><th scope="row"><label>Username</label></th><td><strong>admin</strong></td></tr>
                        <tr><th scope="row"><label for="email">Email</label></th><td><input name="email" type="email" id="email" value="admin@prestoworld.org" class="regular-text" /></td></tr>
                        <tr><th scope="row"><label for="first_name">First Name</label></th><td><input name="first_name" type="text" id="first_name" class="regular-text" /></td></tr>
                        <tr><th scope="row"><label for="last_name">Last Name</label></th><td><input name="last_name" type="text" id="last_name" class="regular-text" /></td></tr>
                        <tr><th scope="row"><label for="display_name">Display Name</label></th><td><input name="display_name" type="text" id="display_name" value="Administrator" class="regular-text" /></td></tr>
                    </table>
                    <p class="submit"><input type="submit" class="button button-primary" value="Update Profile" /></p>
                </div>
            </div>

        <?php elseif ($activeScreen === 'tools'): ?>

            <div id="post-body-content">
                <div class="notice notice-info inline">
                    <p>Available Tools — various tools for managing your site.</p>
                </div>
                <div style="display:flex;gap:16px;flex-wrap:wrap;">
                    <div style="flex:1;min-width:250px;background:#fff;border:1px solid #dcdcde;padding:16px;">
                        <h2 style="font-size:14px;font-weight:600;margin:0 0 8px;">Import</h2>
                        <p style="font-size:12px;color:#50575e;">Import content from other systems.</p>
                        <a href="/wp-admin/import.php" class="button">Import</a>
                    </div>
                    <div style="flex:1;min-width:250px;background:#fff;border:1px solid #dcdcde;padding:16px;">
                        <h2 style="font-size:14px;font-weight:600;margin:0 0 8px;">Export</h2>
                        <p style="font-size:12px;color:#50575e;">Export your content as XML.</p>
                        <a href="/wp-admin/export.php" class="button">Export</a>
                    </div>
                    <div style="flex:1;min-width:250px;background:#fff;border:1px solid #dcdcde;padding:16px;">
                        <h2 style="font-size:14px;font-weight:600;margin:0 0 8px;">Site Health</h2>
                        <p style="font-size:12px;color:#50575e;">Check the health of your site.</p>
                        <a href="/wp-admin/site-health.php" class="button">View</a>
                    </div>
                </div>
            </div>

        <?php elseif ($activeScreen === 'import'): ?>

            <div id="post-body-content">
                <div class="notice notice-info inline">
                    <p>Import content from WordPress, Blogger, Tumblr, or other sources.</p>
                </div>
                <div style="background:#fff;border:1px solid #dcdcde;padding:20px;">
                    <table class="form-table">
                        <tr><th>WordPress</th><td>Import posts, pages, comments, and more from a WordPress export file.<br /><button class="button">Install Importer</button></td></tr>
                        <tr><th>Blogger</th><td>Import posts and comments from Blogger.<br /><button class="button">Install Importer</button></td></tr>
                        <tr><th>RSS</th><td>Import posts from an RSS feed.<br /><button class="button">Install Importer</button></td></tr>
                    </table>
                </div>
            </div>

        <?php elseif ($activeScreen === 'export'): ?>

            <div id="post-body-content">
                <div class="notice notice-info inline">
                    <p>Export your content as a WordPress eXtended RSS (WXR) XML file.</p>
                </div>
                <div style="background:#fff;border:1px solid #dcdcde;padding:20px;">
                    <fieldset style="border:none;padding:0;">
                        <legend style="font-weight:600;margin-bottom:8px;">Choose what to export:</legend>
                        <label style="display:block;margin-bottom:4px;"><input type="radio" name="content" checked /> All content</label>
                        <label style="display:block;margin-bottom:4px;"><input type="radio" name="content" /> Posts</label>
                        <label style="display:block;margin-bottom:4px;"><input type="radio" name="content" /> Pages</label>
                        <label style="display:block;margin-bottom:4px;"><input type="radio" name="content" /> Media</label>
                    </fieldset>
                    <p class="submit"><input type="submit" class="button button-primary" value="Download Export File" /></p>
                </div>
            </div>

        <?php elseif ($activeScreen === 'site-health'): ?>

            <div id="post-body-content">
                <div class="notice notice-info inline">
                    <p>Site Health checks the overall health of your WordPress installation.</p>
                </div>
                <div style="display:flex;gap:24px;">
                    <div style="flex:1;background:#fff;border:1px solid #dcdcde;padding:20px;">
                        <h2 style="font-size:14px;font-weight:600;margin:0 0 12px;">Status</h2>
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="width:80px;height:80px;border-radius:50%;background:#f0f0f1;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:700;color:#787c82;">—</div>
                            <div>
                                <p style="margin:0;font-weight:600;">Site health not yet tested</p>
                                <p style="margin:4px 0 0;font-size:12px;color:#787c82;">Run the health check to see recommendations.</p>
                            </div>
                        </div>
                        <p class="submit" style="margin-top:16px;"><button class="button button-primary">Run Site Health Check</button></p>
                    </div>
                    <div style="width:300px;background:#fff;border:1px solid #dcdcde;padding:20px;">
                        <h2 style="font-size:14px;font-weight:600;margin:0 0 12px;">Info</h2>
                        <a href="/wp-admin/site-health-info.php" class="button" style="width:100%;text-align:center;">View Site Info</a>
                    </div>
                </div>
            </div>

        <?php elseif (in_array($activeScreen, ['settings', 'options-writing', 'options-reading', 'options-discussion', 'options-media', 'options-permalink', 'options-privacy'], true)): ?>

            <?php
            $settingTabs = [
                'settings'          => ['General', 'options-general.php'],
                'options-writing'   => ['Writing', 'options-writing.php'],
                'options-reading'   => ['Reading', 'options-reading.php'],
                'options-discussion'=> ['Discussion', 'options-discussion.php'],
                'options-media'     => ['Media', 'options-media.php'],
                'options-permalink' => ['Permalinks', 'options-permalink.php'],
                'options-privacy'   => ['Privacy', 'options-privacy.php'],
            ];
            ?>
            <div id="post-body-content">
                <nav style="margin-bottom:16px;border-bottom:1px solid #dcdcde;display:flex;gap:0;">
                    <?php foreach ($settingTabs as $id => [$label, $url]): ?>
                    <a href="/wp-admin/<?= $url ?>"
                       style="display:inline-block;padding:8px 16px;text-decoration:none;font-size:13px;<?= $id === $activeScreen ? 'background:#fff;border:1px solid #dcdcde;border-bottom:1px solid #fff;margin-bottom:-1px;color:#2271b1;font-weight:600;' : 'color:#50575e;' ?>">
                        <?= $label ?>
                    </a>
                    <?php endforeach; ?>
                </nav>

                <?php if ($activeScreen === 'settings'): ?>
                <form action="" method="post">
                    <table class="form-table">
                        <tr><th scope="row"><label>Site Title</label></th><td><input name="site-title" type="text" value="PrestoWorld" class="regular-text" /></td></tr>
                        <tr><th scope="row"><label>Tagline</label></th><td><input name="site-tagline" type="text" value="Digital marketplace platform" class="regular-text" /></td></tr>
                        <tr><th scope="row"><label>WordPress Address (URL)</label></th><td><input name="siteurl" type="url" value="https://prestoworld.org" class="regular-text code" /></td></tr>
                        <tr><th scope="row"><label>Site Address (URL)</label></th><td><input name="home" type="url" value="https://prestoworld.org" class="regular-text code" /></td></tr>
                        <tr><th scope="row"><label>Administration Email Address</label></th><td><input name="admin_email" type="email" value="admin@prestoworld.org" class="regular-text" /></td></tr>
                        <tr><th scope="row"><label>Membership</label></th><td><label><input type="checkbox" /> Anyone can register</label></td></tr>
                        <tr><th scope="row"><label>New User Default Role</label></th><td><select><option>Subscriber</option><option>Contributor</option><option>Author</option><option>Editor</option><option>Administrator</option></select></td></tr>
                        <tr><th scope="row"><label>Site Language</label></th><td><select><option>English (United States)</option></select></td></tr>
                        <tr><th scope="row"><label>Timezone</label></th><td><select><option>UTC</option></select></td></tr>
                        <tr><th scope="row"><label>Date Format</label></th><td><select><option>F j, Y</option></select></td></tr>
                        <tr><th scope="row"><label>Time Format</label></th><td><select><option>g:i a</option></select></td></tr>
                        <tr><th scope="row"><label>Week Starts On</label></th><td><select><option>Monday</option></select></td></tr>
                    </table>
                    <p class="submit"><input type="submit" class="button button-primary" value="Save Changes" /></p>
                </form>

                <?php elseif ($activeScreen === 'options-writing'): ?>
                <form action="" method="post">
                    <table class="form-table">
                        <tr><th scope="row"><label>Default Post Category</label></th><td><select><option>Uncategorized</option></select></td></tr>
                        <tr><th scope="row"><label>Default Post Format</label></th><td><select><option>Standard</option></select></td></tr>
                        <tr><th scope="row"><label>Post via email</label></th><td><p style="color:#787c82;">Configure a secret email address to post by email.</p></td></tr>
                        <tr><th scope="row"><label>Remote Publishing</label></th><td><label><input type="checkbox" /> Enable the XML-RPC publishing protocol.</label></td></tr>
                    </table>
                    <p class="submit"><input type="submit" class="button button-primary" value="Save Changes" /></p>
                </form>

                <?php elseif ($activeScreen === 'options-reading'): ?>
                <form action="" method="post">
                    <table class="form-table">
                        <tr><th scope="row"><label>Your homepage displays</label></th>
                            <td>
                                <label><input type="radio" name="show_on_front" checked /> Your latest posts</label><br />
                                <label><input type="radio" name="show_on_front" /> A static page</label>
                            </td>
                        </tr>
                        <tr><th scope="row"><label>Blog pages show at most</label></th><td><input type="number" value="10" class="small-text" /> posts</td></tr>
                        <tr><th scope="row"><label>Syndication feeds show the most recent</label></th><td><input type="number" value="10" class="small-text" /> items</td></tr>
                        <tr><th scope="row"><label>For each post in a feed, show</label></th>
                            <td><label><input type="radio" name="rss_use_excerpt" checked /> Full text</label><br />
                                <label><input type="radio" name="rss_use_excerpt" /> Summary</label></td>
                        </tr>
                        <tr><th scope="row"><label>Search Engine Visibility</label></th>
                            <td><label><input type="checkbox" /> Discourage search engines from indexing this site</label></td>
                        </tr>
                    </table>
                    <p class="submit"><input type="submit" class="button button-primary" value="Save Changes" /></p>
                </form>

                <?php elseif ($activeScreen === 'options-discussion'): ?>
                <form action="" method="post">
                    <table class="form-table">
                        <tr><th scope="row">Default post settings</th>
                            <td><label><input type="checkbox" checked /> Attempt to notify any blogs linked to from the post</label><br />
                                <label><input type="checkbox" checked /> Allow link notifications from other blogs (pingbacks and trackbacks)</label></td>
                        </tr>
                        <tr><th scope="row"><label>Allow people to post comments on new articles</label></th>
                            <td><label><input type="checkbox" checked /> Allow people to submit comments on new posts</label></td>
                        </tr>
                        <tr><th scope="row"><label>Comment must be manually approved</label></th>
                            <td><label><input type="checkbox" /> Comment author must have a previously approved comment</label></td>
                        </tr>
                        <tr><th scope="row"><label>Comment moderation</label></th>
                            <td><textarea rows="4" class="large-text code" placeholder="Hold a comment in the queue if it contains X links. Separate words with commas."></textarea></td>
                        </tr>
                        <tr><th scope="row"><label>Avatars</label></th>
                            <td><label><input type="checkbox" checked /> Show avatars</label><br />
                                <select><option>Mystery Person</option></select></td>
                        </tr>
                    </table>
                    <p class="submit"><input type="submit" class="button button-primary" value="Save Changes" /></p>
                </form>

                <?php elseif ($activeScreen === 'options-media'): ?>
                <form action="" method="post">
                    <table class="form-table">
                        <tr><th scope="row"><label>Thumbnail size</label></th>
                            <td>Width: <input type="number" value="150" class="small-text" /> Height: <input type="number" value="150" class="small-text" /><br />
                                <label><input type="checkbox" checked /> Crop thumbnail to exact dimensions</label></td>
                        </tr>
                        <tr><th scope="row"><label>Medium size</label></th>
                            <td>Max Width: <input type="number" value="300" class="small-text" /> Max Height: <input type="number" value="300" class="small-text" /></td>
                        </tr>
                        <tr><th scope="row"><label>Large size</label></th>
                            <td>Max Width: <input type="number" value="1024" class="small-text" /> Max Height: <input type="number" value="1024" class="small-text" /></td>
                        </tr>
                        <tr><th scope="row"><label>Uploading Files</label></th>
                            <td><label><input type="checkbox" /> Organize my uploads into month- and year-based folders</label></td>
                        </tr>
                    </table>
                    <p class="submit"><input type="submit" class="button button-primary" value="Save Changes" /></p>
                </form>

                <?php elseif ($activeScreen === 'options-permalink'): ?>
                <form action="" method="post">
                    <table class="form-table">
                        <tr><th scope="row"><label>Common Settings</label></th>
                            <td>
                                <label><input type="radio" name="permalink_structure" /> Plain</label><br />
                                <label><input type="radio" name="permalink_structure" /> Day and name</label><br />
                                <label><input type="radio" name="permalink_structure" checked /> Post name</label><br />
                                <label><input type="radio" name="permalink_structure" /> Custom Structure</label><br />
                                <input type="text" value="/%postname%/" class="regular-text code" style="margin-top:4px;" />
                            </td>
                        </tr>
                        <tr><th scope="row"><label>Optional</label></th>
                            <td><input type="text" class="regular-text code" placeholder="category" /> Category base<br />
                                <input type="text" class="regular-text code" placeholder="tags" style="margin-top:4px;" /> Tag base</td>
                        </tr>
                    </table>
                    <p class="submit"><input type="submit" class="button button-primary" value="Save Changes" /></p>
                </form>

                <?php elseif ($activeScreen === 'options-privacy'): ?>
                <form action="" method="post">
                    <div class="notice notice-info inline"><p>Manage your privacy settings and create a privacy policy page.</p></div>
                    <table class="form-table">
                        <tr><th scope="row"><label>Privacy Policy Page</label></th>
                            <td><select><option>— Select —</option></select>
                                <p style="font-size:12px;color:#787c82;">Select a page to use as your privacy policy.</p></td>
                        </tr>
                    </table>
                    <p class="submit"><input type="submit" class="button button-primary" value="Save Changes" /></p>
                </form>
                <?php endif; ?>
            </div>

        <?php elseif ($activeScreen === 'update-core'): ?>

            <div id="post-body-content">
                <div class="notice notice-info inline">
                    <p>WordPress Updates — check for updates to WordPress core, plugins, and themes.</p>
                </div>
                <div style="background:#fff;border:1px solid #dcdcde;padding:20px;">
                    <h2 style="font-size:14px;font-weight:600;margin:0 0 12px;">WordPress Updates</h2>
                    <p>You are running <strong>PrestoWorld</strong>. No updates available at this time.</p>
                    <button class="button">Check Again</button>
                </div>
                <div style="background:#fff;border:1px solid #dcdcde;padding:20px;margin-top:16px;">
                    <h2 style="font-size:14px;font-weight:600;margin:0 0 12px;">Plugins</h2>
                    <p>No plugin updates available.</p>
                </div>
                <div style="background:#fff;border:1px solid #dcdcde;padding:20px;margin-top:16px;">
                    <h2 style="font-size:14px;font-weight:600;margin:0 0 12px;">Themes</h2>
                    <p>No theme updates available.</p>
                </div>
            </div>

        <?php else: ?>

            <div id="post-body-content">
                <div class="notice notice-warning inline">
                    <p>Screen not found: <code><?= htmlspecialchars($activeScreen) ?></code></p>
                </div>
            </div>

        <?php endif; ?>

    </div>
</div>
