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
                            $postCount = $db->select()->from($pfx . 'posts')->where('status', '!=', 'auto-draft')->count();
                            $userCount = $db->select()->from($pfx . 'users')->count();
                            $commentCount = $db->select()->from($pfx . 'comments')->count();
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
            $posts = $db->select('*')->from($pfx . 'posts')->where('post_type', 'post')->where('status', '!=', 'auto-draft')->orderBy('created_at', 'DESC')->fetchAll();
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
                                <strong><a href="<?= htmlspecialchars($__screenUrl('post')) ?>?post=<?= $pid ?>"><?= htmlspecialchars($p['title'] ?? '') ?></a></strong>
                                <div class="row-actions">
                                    <span class="edit"><a href="<?= htmlspecialchars($__screenUrl('post')) ?>?post=<?= $pid ?>&action=edit">Edit</a></span>
                                    <span class="trash"><a href="#">Trash</a></span>
                                    <span class="view"><a href="/?p=<?= $pid ?>">View</a></span>
                                </div>
                            </td>
                            <td class="author column-author">admin</td>
                            <td class="categories column-categories">Uncategorized</td>
                            <td class="date column-date"><?= htmlspecialchars(date('Y-m-d', strtotime($p['created_at'] ?? ''))) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($activeScreen === 'post-new' || $activeScreen === 'post'): ?>

            <?php
            $isEdit = $activeScreen === 'post';
            $postId = $isEdit ? (int) ($_GET['post'] ?? 0) : 0;
            $pfx = getenv('PW_TABLE_PREFIX') ?: 'pw_';
            $postData = [
                'title' => '', 'content' => '', 'excerpt' => '', 'status' => 'draft',
                'slug' => '', 'visibility' => 'public', 'password' => '',
                'featured_image' => '', 'categories' => [], 'tags' => [],
                'created_at' => '',
            ];
            $allCategories = [];
            $allTags = [];
            $db = \Witals\Framework\Container\Container::getInstance()
                ?->make(\Cycle\Database\DatabaseInterface::class);

            if ($db !== null) {
                try {
                    $allCategories = $db->select('*')->from($pfx . 'terms')
                        ->where('taxonomy', 'category')->orderBy('name', 'ASC')->fetchAll();
                } catch (\Throwable) { $allCategories = []; }
                try {
                    $allTags = $db->select('*')->from($pfx . 'terms')
                        ->where('taxonomy', 'post_tag')->orderBy('name', 'ASC')->fetchAll();
                } catch (\Throwable) { $allTags = []; }

                if ($postId > 0) {
                    try {
                        $row = $db->select('*')->from($pfx . 'posts')->where('id', $postId)->run()->fetch();
                        if ($row) {
                            $meta = is_string($row['compact_meta'] ?? null)
                                ? json_decode($row['compact_meta'], true) : ($row['compact_meta'] ?? []);
                            $assignedTerms = $db->select('term_id')->from($pfx . 'term_relationships')
                                ->where('object_id', $postId)->fetchAll();
                            $assignedIds = array_map(fn($t) => (int) ($t['term_id'] ?? 0), $assignedTerms);
                            $postData = [
                                'title' => $row['title'] ?? '',
                                'content' => $meta['content'] ?? '',
                                'excerpt' => $meta['excerpt'] ?? '',
                                'status' => $row['status'] ?? 'draft',
                                'slug' => $row['slug'] ?? '',
                                'visibility' => $meta['visibility'] ?? ($row['status'] === 'private' ? 'private' : 'public'),
                                'password' => $meta['password'] ?? '',
                                'featured_image' => $meta['featured_image'] ?? '',
                                'categories' => $assignedIds,
                                'tags' => [],
                                'created_at' => $row['created_at'] ?? '',
                            ];
                            foreach ($assignedTerms as $t) {
                                $tid = (int) ($t['term_id'] ?? 0);
                                foreach ($allTags as $tag) {
                                    if ((int) ($tag['id'] ?? 0) === $tid) {
                                        $postData['tags'][] = $tag['name'] ?? '';
                                    }
                                }
                            }
                        }
                    } catch (\Throwable) {}
                }
            }
            $catIds = array_map(fn($c) => (int) ($c['id'] ?? 0), $postData['categories']);

            $formAction = $isEdit ? '/wp-admin/post.php' : '/wp-admin/post-new.php';
            $buttonLabel = $isEdit ? 'Update' : 'Publish';
            $noticeMsg = '';
            if (isset($_GET['saved'])) $noticeMsg = 'Post saved.';
            elseif (isset($_GET['updated'])) $noticeMsg = 'Post updated.';
            ?>
            <style>
            .pw-editor-wrap { display:flex; gap:20px; }
            .pw-editor-main { flex:1; min-width:0; }
            .pw-editor-side { width:280px; flex-shrink:0; }
            .pw-meta-box { background:#fff; border:1px solid #dcdcde; margin-bottom:12px; }
            .pw-meta-box-title { padding:8px 12px; font-size:13px; font-weight:600; border-bottom:1px solid #dcdcde; cursor:default; }
            .pw-meta-box-inside { padding:8px 12px; font-size:12px; }
            .pw-meta-box-inside label { display:block; margin-bottom:4px; color:#2c3338; font-weight:500; }
            .pw-meta-box-inside input[type="text"],
            .pw-meta-box-inside input[type="password"],
            .pw-meta-box-inside input[type="datetime-local"],
            .pw-meta-box-inside select,
            .pw-meta-box-inside textarea { width:100%; box-sizing:border-box; }
            .pw-meta-box-inside input[type="checkbox"] { margin-right:4px; }
            .pw-publish-row { display:flex; gap:8px; margin-bottom:8px; }
            .pw-publish-row .button { flex:1; text-align:center; }
            .pw-tag-list { display:flex; flex-wrap:wrap; gap:4px; margin-top:4px; }
            .pw-tag-item { background:#f0f0f1; border:1px solid #dcdcde; border-radius:3px; padding:2px 8px; font-size:11px; display:inline-flex; align-items:center; gap:4px; }
            .pw-tag-item .remove { cursor:pointer; color:#b32d2e; font-weight:700; }
            .pw-feat-img { max-width:100%; max-height:150px; display:block; margin:4px 0; border:1px solid #dcdcde; }
            .pw-cat-list { max-height:180px; overflow-y:auto; }
            .pw-cat-list label { font-weight:400; font-size:12px; }
            </style>
            <div id="post-body-content">
                <?php if ($noticeMsg !== ''): ?>
                <div class="notice notice-success inline"><p><strong><?= $noticeMsg ?></strong></p></div>
                <?php endif; ?>

                <form action="<?= $formAction ?>" method="post" id="post-editor-form">
                    <input type="hidden" name="post_id" value="<?= $postId ?>" />
                    <input type="hidden" name="post_type" value="post" />

                    <div class="pw-editor-wrap">

                        <div class="pw-editor-main">
                            <div style="background:#fff;border:1px solid #dcdcde;padding:16px;margin-bottom:12px;">
                                <input name="title" type="text" value="<?= htmlspecialchars($postData['title']) ?>"
                                    style="width:100%;padding:8px;font-size:16px;border:1px solid #8c8f94;border-radius:4px;"
                                    placeholder="Add title" />
                            </div>

                            <div style="background:#fff;border:1px solid #dcdcde;padding:16px;margin-bottom:12px;">
                                <label style="display:block;font-weight:600;margin-bottom:4px;">Content</label>
                                <textarea name="content" rows="20"
                                    style="width:100%;padding:8px;border:1px solid #8c8f94;border-radius:4px;font-family:monospace;"><?= htmlspecialchars($postData['content']) ?></textarea>
                            </div>

                            <div style="background:#fff;border:1px solid #dcdcde;padding:16px;margin-bottom:12px;">
                                <label style="display:block;font-weight:600;margin-bottom:4px;">Excerpt</label>
                                <textarea name="excerpt" rows="3"
                                    style="width:100%;padding:8px;border:1px solid #8c8f94;border-radius:4px;"><?= htmlspecialchars($postData['excerpt']) ?></textarea>
                                <p style="color:#787c82;font-size:12px;margin:4px 0 0;">Excerpts are optional hand-crafted summaries of your content.</p>
                            </div>
                        </div>

                        <div class="pw-editor-side">

                            <div class="pw-meta-box">
                                <div class="pw-meta-box-title">Publish</div>
                                <div class="pw-meta-box-inside">
                                    <div class="pw-publish-row">
                                        <input type="submit" name="submit_save" class="button" value="Save Draft" />
                                        <input type="submit" name="submit_publish" class="button button-primary" value="<?= $buttonLabel ?>" />
                                    </div>

                                    <label>Status</label>
                                    <select name="status" style="margin-bottom:8px;">
                                        <option value="draft"<?= $postData['status'] === 'draft' ? ' selected' : '' ?>>Draft</option>
                                        <option value="pending"<?= $postData['status'] === 'pending' ? ' selected' : '' ?>>Pending Review</option>
                                        <option value="publish"<?= $postData['status'] === 'publish' ? ' selected' : '' ?>>Published</option>
                                        <option value="private"<?= $postData['status'] === 'private' ? ' selected' : '' ?>>Privately Published</option>
                                    </select>

                                    <label>Visibility</label>
                                    <div style="margin-bottom:8px;">
                                        <label><input type="radio" name="visibility" value="public"<?= $postData['visibility'] === 'public' ? ' checked' : '' ?> onclick="document.getElementById('pw-password-field').style.display='none'" /> Public</label><br />
                                        <label><input type="radio" name="visibility" value="password"<?= $postData['visibility'] === 'password' ? ' checked' : '' ?> onclick="document.getElementById('pw-password-field').style.display='block'" /> Password protected</label><br />
                                        <label><input type="radio" name="visibility" value="private"<?= $postData['visibility'] === 'private' ? ' checked' : '' ?> onclick="document.getElementById('pw-password-field').style.display='none'" /> Private</label>
                                        <div id="pw-password-field" style="<?= $postData['visibility'] === 'password' ? '' : 'display:none' ?>;margin-top:4px;">
                                            <input name="password" type="password" value="<?= htmlspecialchars($postData['password']) ?>" placeholder="Enter password" />
                                        </div>
                                    </div>

                                    <?php if ($isEdit && $postData['created_at'] !== ''): ?>
                                    <label>Published on</label>
                                    <input name="publish_date" type="datetime-local" value="<?= date('Y-m-d\TH:i', strtotime($postData['created_at'])) ?>" />
                                    <?php endif; ?>

                                    <?php if ($isEdit): ?>
                                    <hr style="margin:8px 0;border:none;border-top:1px solid #dcdcde;" />
                                    <a href="#" onclick="if(confirm('Are you sure you want to move this post to Trash?')){document.getElementById('post-editor-form').action='/wp-admin/post.php?trash=1';document.getElementById('post-editor-form').submit();}return false;" style="color:#b32d2e;font-size:12px;">Move to Trash</a>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="pw-meta-box">
                                <div class="pw-meta-box-title">Slug</div>
                                <div class="pw-meta-box-inside">
                                    <input name="slug" type="text" value="<?= htmlspecialchars($postData['slug']) ?>" placeholder="auto-generated" />
                                </div>
                            </div>

                            <div class="pw-meta-box">
                                <div class="pw-meta-box-title">Categories</div>
                                <div class="pw-meta-box-inside">
                                    <div class="pw-cat-list">
                                        <?php if (empty($allCategories)): ?>
                                        <p style="color:#787c82;">No categories found.</p>
                                        <?php else: ?>
                                        <?php foreach ($allCategories as $cat): $cid = (int) ($cat['id'] ?? 0); ?>
                                        <label><input type="checkbox" name="categories[]" value="<?= $cid ?>"<?= in_array($cid, $catIds, true) ? ' checked' : '' ?> /> <?= htmlspecialchars($cat['name'] ?? '') ?></label><br />
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <hr style="margin:8px 0;border:none;border-top:1px solid #dcdcde;" />
                                    <input name="new_category" type="text" placeholder="+ Add New Category" style="font-size:12px;" />
                                </div>
                            </div>

                            <div class="pw-meta-box">
                                <div class="pw-meta-box-title">Tags</div>
                                <div class="pw-meta-box-inside">
                                    <input name="tags" type="text" value="<?= htmlspecialchars(implode(', ', $postData['tags'])) ?>" placeholder="Separate with commas" style="font-size:12px;" />
                                    <p style="color:#787c82;font-size:11px;margin:4px 0 0;">Separate tags with commas.</p>
                                </div>
                            </div>

                            <div class="pw-meta-box">
                                <div class="pw-meta-box-title">Featured Image</div>
                                <div class="pw-meta-box-inside" id="pw-feat-image-inside">
                                    <?php if ($postData['featured_image'] !== ''): ?>
                                    <img src="<?= htmlspecialchars($postData['featured_image']) ?>" class="pw-feat-img" style="max-width:100%;height:auto;margin-bottom:8px;border-radius:4px;" />
                                    <?php endif; ?>
                                    <input name="featured_image" type="hidden" value="<?= htmlspecialchars($postData['featured_image']) ?>" />
                                    <div id="featured-image-picker"></div>
                                </div>
                            </div>

                        </div>
                    </div>
                </form>
            </div>

            <style>
            .pw-feat-preview-wrap { margin-bottom:8px; display:inline-block; position:relative; }
            .pw-feat-hidden { display:none !important; }
            .pw-feat-picker-thumb { max-width:100%; height:auto; display:block; border-radius:4px; border:1px solid #dcdcde; }
            .pw-feat-remove-btn { position:absolute; top:4px; right:4px; background:rgba(0,0,0,0.6); color:#fff; border:none; border-radius:50%; width:24px; height:24px; font-size:16px; line-height:1; cursor:pointer; display:flex; align-items:center; justify-content:center; opacity:0; transition:opacity 0.15s; }
            .pw-feat-preview-wrap:hover .pw-feat-remove-btn { opacity:1; }
            .pw-feat-remove-btn:hover { background:rgba(214,54,56,0.8); }
            .pw-feat-picker-btn { font-size:12px !important; }
            .pw-feat-modal-overlay { position:fixed; inset:0; z-index:100000; background:rgba(0,0,0,0.5); display:flex; align-items:center; justify-content:center; padding:20px; }
            .pw-feat-modal { background:#fff; border-radius:8px; width:100%; max-width:640px; max-height:80vh; display:flex; flex-direction:column; box-shadow:0 20px 60px rgba(0,0,0,0.3); }
            .pw-feat-modal-header { display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid #dcdcde; }
            .pw-feat-modal-header h2 { margin:0; font-size:16px; font-weight:600; }
            .pw-feat-modal-close { background:none; border:none; cursor:pointer; color:#787c82; padding:4px 8px; font-size:18px; border-radius:4px; }
            .pw-feat-modal-close:hover { color:#1d2327; background:#f0f0f1; }
            .pw-feat-modal-tabs { display:flex; border-bottom:1px solid #dcdcde; background:#f6f7f7; }
            .pw-feat-modal-tabs button { flex:1; padding:10px 16px; border:none; background:transparent; cursor:pointer; font-size:13px; font-weight:500; color:#787c82; border-bottom:2px solid transparent; transition:all 0.1s; }
            .pw-feat-modal-tabs button:hover { color:#1d2327; background:#fff; }
            .pw-feat-tab-active { color:#2271b1 !important; border-bottom-color:#2271b1 !important; background:#fff !important; }
            .pw-feat-modal-body { flex:1; overflow-y:auto; padding:16px 20px; min-height:300px; }
            .pw-feat-loading, .pw-feat-empty { display:flex; align-items:center; justify-content:center; padding:60px 20px; color:#787c82; font-size:13px; }
            .pw-feat-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(140px,1fr)); gap:12px; }
            .pw-feat-grid-item { position:relative; border:2px solid #dcdcde; border-radius:6px; overflow:hidden; cursor:pointer; transition:border-color 0.15s; background:#f6f7f7; }
            .pw-feat-grid-item:hover { border-color:#2271b1; }
            .pw-feat-grid-item img { width:100%; aspect-ratio:1; object-fit:cover; display:block; }
            .pw-feat-item-info { padding:6px 8px; background:#fff; border-top:1px solid #f0f0f1; }
            .pw-feat-item-name { display:block; font-size:11px; font-weight:600; color:#3c434a; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
            .pw-feat-item-size { display:block; font-size:10px; color:#787c82; font-family:monospace; }
            .pw-feat-upload-zone { border:2px dashed #dcdcde; border-radius:8px; padding:40px 20px; text-align:center; cursor:pointer; color:#787c82; transition:all 0.15s; }
            .pw-feat-upload-zone:hover, .pw-feat-dragover { border-color:#2271b1; background:#f0f6fc; color:#1d2327; }
            .pw-feat-upload-zone p { margin:8px 0 0; font-size:13px; }
            .pw-feat-uploading { display:flex; align-items:center; gap:8px; padding:12px 16px; background:#f0f6fc; border-radius:6px; margin-top:12px; font-size:13px; color:#2271b1; }
            </style>

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                var form = document.getElementById('post-editor-form');
                if (form) {
                    form.addEventListener('submit', function(e) {
                        var btn = e.submitter;
                        if (btn && btn.name === 'submit_save') {
                            var statusSelect = form.querySelector('[name="status"]');
                            if (statusSelect && statusSelect.value === 'publish') {
                                statusSelect.value = 'draft';
                            }
                        }
                    });
                }

                (function() {
                    var mount = document.getElementById('featured-image-picker');
                    if (!mount) return;

                    var input = document.querySelector('input[name="featured_image"]');
                    var previewImg = document.querySelector('.pw-feat-img');

                    function render() {
                        var currentUrl = input ? input.value : '';

                        mount.innerHTML =
                            '<div class="pw-feat-preview-wrap' + (currentUrl ? '' : ' pw-feat-hidden') + '">' +
                                '<img src="' + escapeHtml(currentUrl) + '" class="pw-feat-picker-thumb" />' +
                                '<button type="button" class="pw-feat-remove-btn" title="Remove featured image">&times;</button>' +
                            '</div>' +
                            '<button type="button" class="button pw-feat-picker-btn" id="pw-feat-open-btn">' +
                                (currentUrl ? 'Replace Image' : 'Set featured image') +
                            '</button>';
                    }

                    function escapeHtml(s) {
                        if (!s) return '';
                        return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
                    }

                    render();

                    function setFeaturedImage(url) {
                        if (input) {
                            input.value = url;
                            input.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                        if (previewImg) {
                            if (url) {
                                previewImg.src = url;
                                previewImg.style.display = '';
                            } else {
                                previewImg.src = '';
                                previewImg.style.display = 'none';
                            }
                        }
                        render();
                    }

                    mount.addEventListener('click', function(e) {
                        if (e.target.closest('.pw-feat-remove-btn')) {
                            setFeaturedImage('');
                            return;
                        }
                        if (e.target.id === 'pw-feat-open-btn') {
                            openModal();
                        }
                    });

                    function openModal() {
                        var overlay = document.createElement('div');
                        overlay.className = 'pw-feat-modal-overlay';
                        overlay.innerHTML =
                            '<div class="pw-feat-modal">' +
                                '<div class="pw-feat-modal-header">' +
                                    '<h2>Featured Image</h2>' +
                                    '<button type="button" class="pw-feat-modal-close" id="pw-modal-close">&times;</button>' +
                                '</div>' +
                                '<div class="pw-feat-modal-tabs">' +
                                    '<button type="button" class="pw-feat-tab pw-feat-tab-active" data-tab="library">Media Library</button>' +
                                    '<button type="button" class="pw-feat-tab" data-tab="upload">Upload</button>' +
                                '</div>' +
                                '<div class="pw-feat-modal-body" id="pw-modal-body">' +
                                    '<div class="pw-feat-loading" id="pw-modal-loading">Loading media...</div>' +
                                '</div>' +
                            '</div>';
                        document.body.appendChild(overlay);

                        var bodyEl = document.getElementById('pw-modal-body');
                        var currentTab = 'library';

                        overlay.addEventListener('click', function(e) {
                            if (e.target === overlay) closeModal(overlay);
                        });

                        document.getElementById('pw-modal-close').addEventListener('click', function() {
                            closeModal(overlay);
                        });

                        overlay.querySelectorAll('.pw-feat-tab').forEach(function(btn) {
                            btn.addEventListener('click', function() {
                                overlay.querySelectorAll('.pw-feat-tab').forEach(function(b) { b.classList.remove('pw-feat-tab-active'); });
                                btn.classList.add('pw-feat-tab-active');
                                currentTab = btn.getAttribute('data-tab');
                                if (currentTab === 'library') loadMediaGrid(bodyEl, overlay);
                                else showUploadTab(bodyEl, overlay);
                            });
                        });

                        loadMediaGrid(bodyEl, overlay);
                    }

                    function closeModal(overlay) {
                        if (overlay && overlay.parentNode) overlay.parentNode.removeChild(overlay);
                    }

                    function loadMediaGrid(container, overlay) {
                        container.innerHTML = '<div class="pw-feat-loading">Loading media...</div>';
                        fetch('/api/admin/media')
                            .then(function(r) { return r.json(); })
                            .then(function(items) {
                                var images = items.filter(function(i) { return i.mimeType && i.mimeType.startsWith('image/'); });
                                if (images.length === 0) {
                                    container.innerHTML = '<div class="pw-feat-empty"><p>No media items found. Upload an image first.</p></div>';
                                    return;
                                }
                                var html = '<div class="pw-feat-grid">';
                                images.forEach(function(item) {
                                    var thumb = item.thumbnailUrl || item.url;
                                    html +=
                                        '<div class="pw-feat-grid-item" data-url="' + escapeHtml(item.url) + '">' +
                                            '<img src="' + escapeHtml(thumb) + '" alt="' + escapeHtml(item.title) + '" loading="lazy" />' +
                                            '<div class="pw-feat-item-info">' +
                                                '<span class="pw-feat-item-name">' + escapeHtml(item.title) + '</span>' +
                                                '<span class="pw-feat-item-size">' + formatSize(item.size) + '</span>' +
                                            '</div>' +
                                        '</div>';
                                });
                                html += '</div>';
                                container.innerHTML = html;

                                container.querySelectorAll('.pw-feat-grid-item').forEach(function(item) {
                                    item.addEventListener('click', function() {
                                        var url = item.getAttribute('data-url');
                                        setFeaturedImage(url);
                                        closeModal(overlay);
                                    });
                                });
                            })
                            .catch(function() {
                                container.innerHTML = '<div class="pw-feat-empty"><p>Failed to load media library.</p></div>';
                            });
                    }

                    function showUploadTab(container, overlay) {
                        container.innerHTML =
                            '<div class="pw-feat-upload-zone" id="pw-upload-zone">' +
                                '<p>Drop an image here or click to browse</p>' +
                                '<input type="file" accept="image/*" id="pw-upload-input" style="display:none" />' +
                            '</div>' +
                            '<div class="pw-feat-uploading pw-feat-hidden" id="pw-upload-progress">Uploading...</div>';

                        var zone = document.getElementById('pw-upload-zone');
                        var fileInput = document.getElementById('pw-upload-input');
                        var progress = document.getElementById('pw-upload-progress');

                        zone.addEventListener('click', function() { fileInput.click(); });
                        zone.addEventListener('dragover', function(e) { e.preventDefault(); zone.classList.add('pw-feat-dragover'); });
                        zone.addEventListener('dragleave', function() { zone.classList.remove('pw-feat-dragover'); });
                        zone.addEventListener('drop', function(e) {
                            e.preventDefault();
                            zone.classList.remove('pw-feat-dragover');
                            var file = e.dataTransfer.files[0];
                            if (file) uploadAndSelect(file, container, overlay);
                        });
                        fileInput.addEventListener('change', function() {
                            if (fileInput.files[0]) uploadAndSelect(fileInput.files[0], container, overlay);
                        });
                    }

                    function uploadAndSelect(file, container, overlay) {
                        if (!file.type.startsWith('image/')) return;
                        var maxSize = 2 * 1024 * 1024; // 2MB (PHP upload_max_filesize)
                        if (file.size > maxSize) {
                            alert('File too large. Maximum size is 2MB. Please increase upload_max_filesize and post_max_size in php.ini for larger files.');
                            return;
                        }
                        var progress = document.getElementById('pw-upload-progress');
                        if (progress) progress.classList.remove('pw-feat-hidden');

                        var formData = new FormData();
                        formData.append('file', file, file.name);

                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', '/wp-admin/media-upload.php', true);
                        xhr.onload = function() {
                            if (xhr.status === 200) {
                                try {
                                    var result = JSON.parse(xhr.responseText);
                                    if (result && result.url) {
                                        setFeaturedImage(result.url);
                                        closeModal(overlay);
                                        return;
                                    }
                                } catch(e) {}
                                if (progress) progress.textContent = 'Invalid response.';
                            } else {
                                var msg = 'Upload failed (HTTP ' + xhr.status + ')';
                                try {
                                    var err = JSON.parse(xhr.responseText);
                                    if (err && err.error) msg = err.error;
                                } catch(e) {}
                                if (progress) progress.textContent = msg;
                            }
                        };
                        xhr.onerror = function() {
                            if (progress) progress.textContent = 'Network error.';
                        };
                        xhr.send(formData);
                    }

                    function formatSize(bytes) {
                        if (bytes === 0) return '0 B';
                        var k = 1024;
                        var sizes = ['B', 'KB', 'MB', 'GB'];
                        var i = Math.floor(Math.log(bytes) / Math.log(k));
                        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
                    }

                    document.addEventListener('keydown', function(e) {
                        if (e.key === 'Escape') {
                            var ov = document.querySelector('.pw-feat-modal-overlay');
                            if (ov) closeModal(ov);
                        }
                    });
                })();
            });
            </script>

        <?php elseif ($activeScreen === 'media-new'): ?>

            <?php
            $uploadUrl = '/wp-admin/upload.php';
            ?>
            <div id="post-body-content">
                <div class="notice notice-info inline"><p>Upload New Media.</p></div>
                <div style="background:#fff;border:1px solid #dcdcde;padding:20px;max-width:600px;">
                    <form action="/api/admin/media/upload" method="post" enctype="multipart/form-data">
                        <div style="margin-bottom:16px;">
                            <label style="display:block;font-weight:600;margin-bottom:4px;">Choose files to upload</label>
                            <input type="file" name="file" multiple style="padding:8px 0;" />
                        </div>
                        <p class="submit">
                            <input type="submit" class="button button-primary" value="Upload" />
                            <a href="<?= $uploadUrl ?>" class="button" style="margin-left:8px;">Media Library</a>
                        </p>
                    </form>
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
                    ->where('status', '!=', 'auto-draft')
                    ->orderBy('created_at', 'DESC')
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
                        ->where('post_id', $id)->where('meta_key', '_wp_attached_file')->limit(1)->fetchAll();
                    $attachedFile = $m[0]['meta_value'] ?? null;
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
                $authorId = (int) ($row['author_id'] ?? 0);
                if ($authorId > 0) {
                    try {
                        $u = $db->select('display_name')->from($pfx . 'users')
                            ->where('ID', $authorId)->limit(1)->fetchAll();
                        $authorName = $u[0]['display_name'] ?? 'Unknown';
                    } catch (\Throwable) {}
                }

                $mediaItems[] = [
                    'id' => $id,
                    'title' => $row['title'] ?: $fileName ?: "(no title)",
                    'file' => $fileName,
                    'url' => $localUrl,
                    'mime' => $mime,
                    'isImage' => $isImage,
                    'author' => $authorName,
                    'date' => date('Y-m-d H:i', strtotime($row['created_at'] ?? '')),
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
            $pages = $db->select('*')->from($pfx . 'posts')->where('post_type', 'page')->where('status', '!=', 'auto-draft')->orderBy('created_at', 'DESC')->fetchAll();
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
                                <strong><a href="<?= htmlspecialchars($__screenUrl('post')) ?>?post=<?= $pid ?>&action=edit"><?= htmlspecialchars($p['title'] ?? '') ?></a></strong>
                                <div class="row-actions">
                                    <span class="edit"><a href="<?= htmlspecialchars($__screenUrl('post')) ?>?post=<?= $pid ?>&action=edit">Edit</a></span>
                                    <span class="view"><a href="/?page_id=<?= $pid ?>">View</a></span>
                                </div>
                            </td>
                            <td class="author column-author">admin</td>
                            <td class="date column-date"><?= htmlspecialchars(date('Y-m-d', strtotime($p['created_at'] ?? ''))) ?></td>
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
                                $caps = $db->select('meta_value')->from($pfx . 'usermeta')->where('user_id', $uid)->where('meta_key', 'wp_capabilities')->limit(1)->fetchAll();
                                if (!empty($caps[0]['meta_value'])) {
                                    $unser = unserialize($caps[0]['meta_value']);
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
            $optionRepo = null;
            if (class_exists(\PrestoWorld\Foundation\Database\OptionRepository::class)) {
                $app = \Witals\Framework\Container\Container::getInstance();
                if ($app !== null && $app->has(\PrestoWorld\Foundation\Database\OptionRepository::class)) {
                    $optionRepo = $app->make(\PrestoWorld\Foundation\Database\OptionRepository::class);
                }
            }
            $opt = fn(string $key, string $default = '') => $optionRepo?->has($key) ? (string) $optionRepo->get($key, $default) : $default;
            $optChecked = fn(string $key, string $expected = '1') => $optionRepo?->has($key) ? ($optionRepo->get($key, '') === $expected ? ' checked' : '') : $expected;
            ?>
            <div id="post-body-content">
                <?php if (isset($_GET['settings_saved'])): ?>
                <div class="notice notice-success inline"><p><strong>Settings saved.</strong></p></div>
                <?php endif; ?>
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
                        <tr><th scope="row"><label>Site Title</label></th><td><input name="blogname" type="text" value="<?= htmlspecialchars($opt('blogname', 'PrestoWorld')) ?>" class="regular-text" /></td></tr>
                        <tr><th scope="row"><label>Tagline</label></th><td><input name="blogdescription" type="text" value="<?= htmlspecialchars($opt('blogdescription', '')) ?>" class="regular-text" /></td></tr>
                        <tr><th scope="row"><label>WordPress Address (URL)</label></th><td><input name="siteurl" type="url" value="<?= htmlspecialchars($opt('siteurl', '/')) ?>" class="regular-text code" /></td></tr>
                        <tr><th scope="row"><label>Site Address (URL)</label></th><td><input name="home" type="url" value="<?= htmlspecialchars($opt('home', '/')) ?>" class="regular-text code" /></td></tr>
                        <tr><th scope="row"><label>Administration Email Address</label></th><td><input name="admin_email" type="email" value="<?= htmlspecialchars($opt('admin_email', 'admin@prestoworld.org')) ?>" class="regular-text" /></td></tr>
                        <tr><th scope="row"><label>Membership</label></th><td><label><input name="users_can_register" type="checkbox" value="1"<?= $optChecked('users_can_register', '1') ?> /> Anyone can register</label></td></tr>
                        <tr><th scope="row"><label>New User Default Role</label></th><td><select name="default_role">
                            <?php $role = $opt('default_role', 'subscriber'); ?>
                            <option value="subscriber"<?= $role === 'subscriber' ? ' selected' : '' ?>>Subscriber</option>
                            <option value="contributor"<?= $role === 'contributor' ? ' selected' : '' ?>>Contributor</option>
                            <option value="author"<?= $role === 'author' ? ' selected' : '' ?>>Author</option>
                            <option value="editor"<?= $role === 'editor' ? ' selected' : '' ?>>Editor</option>
                            <option value="administrator"<?= $role === 'administrator' ? ' selected' : '' ?>>Administrator</option>
                        </select></td></tr>
                        <tr><th scope="row"><label>Timezone</label></th><td><input name="timezone_string" type="text" value="<?= htmlspecialchars($opt('timezone_string', 'UTC')) ?>" class="regular-text" /></td></tr>
                        <tr><th scope="row"><label>Date Format</label></th><td><input name="date_format" type="text" value="<?= htmlspecialchars($opt('date_format', 'F j, Y')) ?>" class="regular-text" /></td></tr>
                        <tr><th scope="row"><label>Time Format</label></th><td><input name="time_format" type="text" value="<?= htmlspecialchars($opt('time_format', 'g:i a')) ?>" class="regular-text" /></td></tr>
                        <tr><th scope="row"><label>Week Starts On</label></th><td><select name="start_of_week">
                            <?php $sow = $opt('start_of_week', '0'); for ($d = 0; $d < 7; $d++): ?>
                            <option value="<?= $d ?>"<?= $sow === (string) $d ? ' selected' : '' ?>><?= [__('Sunday'), __('Monday'), __('Tuesday'), __('Wednesday'), __('Thursday'), __('Friday'), __('Saturday')][$d] ?></option>
                            <?php endfor; ?>
                        </select></td></tr>
                    </table>
                    <p class="submit"><input type="submit" class="button button-primary" value="Save Changes" /></p>
                </form>

                <?php elseif ($activeScreen === 'options-writing'): ?>
                <form action="" method="post">
                    <table class="form-table">
                        <tr><th scope="row"><label>Default Post Category</label></th><td><input name="default_category" type="text" value="<?= htmlspecialchars($opt('default_category', '1')) ?>" class="small-text" /></td></tr>
                        <tr><th scope="row"><label>Default Post Format</label></th><td><input name="default_post_format" type="text" value="<?= htmlspecialchars($opt('default_post_format', '0')) ?>" class="small-text" /></td></tr>
                        <tr><th scope="row"><label>Post via email</label></th><td><p style="color:#787c82;">Configure a secret email address to post by email.</p></td></tr>
                        <tr><th scope="row"><label>Remote Publishing</label></th><td><label><input name="enable_xmlrpc" type="checkbox" value="1"<?= $optChecked('enable_xmlrpc', '1') ?> /> Enable the XML-RPC publishing protocol.</label></td></tr>
                    </table>
                    <p class="submit"><input type="submit" class="button button-primary" value="Save Changes" /></p>
                </form>

                <?php elseif ($activeScreen === 'options-reading'): ?>
                <form action="" method="post">
                    <table class="form-table">
                        <tr><th scope="row"><label>Your homepage displays</label></th>
                            <td>
                                <?php $sof = $opt('show_on_front', 'posts'); ?>
                                <label><input type="radio" name="show_on_front" value="posts"<?= $sof === 'posts' ? ' checked' : '' ?> /> Your latest posts</label><br />
                                <label><input type="radio" name="show_on_front" value="page"<?= $sof === 'page' ? ' checked' : '' ?> /> A static page</label>
                            </td>
                        </tr>
                        <tr><th scope="row"><label>Blog pages show at most</label></th><td><input name="posts_per_page" type="number" value="<?= htmlspecialchars($opt('posts_per_page', '10')) ?>" class="small-text" /> posts</td></tr>
                        <tr><th scope="row"><label>For each post in a feed, show</label></th>
                            <td><?php $rss = $opt('rss_use_excerpt', '0'); ?>
                                <label><input type="radio" name="rss_use_excerpt" value="0"<?= $rss === '0' ? ' checked' : '' ?> /> Full text</label><br />
                                <label><input type="radio" name="rss_use_excerpt" value="1"<?= $rss === '1' ? ' checked' : '' ?> /> Summary</label></td>
                        </tr>
                        <tr><th scope="row"><label>Search Engine Visibility</label></th>
                            <td><label><input name="blog_public" type="checkbox" value="1"<?= $optChecked('blog_public', '0') ?> /> Discourage search engines from indexing this site</label></td>
                        </tr>
                    </table>
                    <p class="submit"><input type="submit" class="button button-primary" value="Save Changes" /></p>
                </form>

                <?php elseif ($activeScreen === 'options-discussion'): ?>
                <form action="" method="post">
                    <table class="form-table">
                        <tr><th scope="row">Default post settings</th>
                            <td><label><input name="default_pingback_flag" type="checkbox" value="1"<?= $optChecked('default_pingback_flag', '1') ?> /> Attempt to notify any blogs linked to from the post</label><br />
                                <label><input name="default_ping_status" type="checkbox" value="open"<?= $optChecked('default_ping_status', 'open') ?> /> Allow link notifications from other blogs (pingbacks and trackbacks)</label></td>
                        </tr>
                        <tr><th scope="row"><label>Allow people to post comments on new articles</label></th>
                            <td><label><input name="default_comment_status" type="checkbox" value="open"<?= $optChecked('default_comment_status', 'open') ?> /> Allow people to submit comments on new posts</label></td>
                        </tr>
                        <tr><th scope="row"><label>Other comment settings</label></th>
                            <td><label><input name="require_name_email" type="checkbox" value="1"<?= $optChecked('require_name_email', '1') ?> /> Comment author must fill out name and email</label><br />
                                <label><input name="comment_registration" type="checkbox" value="1"<?= $optChecked('comment_registration', '1') ?> /> Users must be registered and logged in to comment</label><br />
                                <label><input name="close_comments_for_old_posts" type="checkbox" value="1"<?= $optChecked('close_comments_for_old_posts', '1') ?> /> Automatically close comments on articles older than <input name="close_comments_days_old" type="number" value="<?= htmlspecialchars($opt('close_comments_days_old', '14')) ?>" class="small-text" /> days</label></td>
                        </tr>
                        <tr><th scope="row"><label>Threaded comments</label></th>
                            <td><label><input name="thread_comments" type="checkbox" value="1"<?= $optChecked('thread_comments', '1') ?> /> Enable threaded (nested) comments <input name="thread_comments_depth" type="number" value="<?= htmlspecialchars($opt('thread_comments_depth', '5')) ?>" class="small-text" /> levels deep</label></td>
                        </tr>
                        <tr><th scope="row"><label>Break comments into pages</label></th>
                            <td><label><input name="page_comments" type="checkbox" value="1"<?= $optChecked('page_comments', '1') ?> /> Break comments into pages with <input name="comments_per_page" type="number" value="<?= htmlspecialchars($opt('comments_per_page', '50')) ?>" class="small-text" /> top level comments per page and the <select name="default_comments_page"><option value="newest"<?= $opt('default_comments_page', 'newest') === 'newest' ? ' selected' : '' ?>>last</option><option value="oldest"<?= $opt('default_comments_page', 'newest') === 'oldest' ? ' selected' : '' ?>>first</option></select> page displayed by default</label></td>
                        </tr>
                        <tr><th scope="row"><label>Comment email notification</label></th>
                            <td><label><input name="comments_notify" type="checkbox" value="1"<?= $optChecked('comments_notify', '1') ?> /> Anyone posts a comment</label><br />
                                &nbsp;&nbsp;&nbsp;<label><input name="moderation_notify" type="checkbox" value="1"<?= $optChecked('moderation_notify', '1') ?> /> A comment is held for moderation</label></td>
                        </tr>
                        <tr><th scope="row"><label>Comment moderation</label></th>
                            <td><label><input name="comment_moderation" type="checkbox" value="1"<?= $optChecked('comment_moderation', '1') ?> /> Comment must be manually approved</label><br />
                                <label><input name="comment_previously_approved" type="checkbox" value="1"<?= $optChecked('comment_previously_approved', '1') ?> /> Comment author must have a previously approved comment</label></td>
                        </tr>
                        <tr><th scope="row"><label>Comment moderation keys</label></th>
                            <td><textarea name="moderation_keys" rows="4" class="large-text code" placeholder="Hold a comment in the queue if it contains X links. Separate words with commas."><?= htmlspecialchars($opt('moderation_keys', '')) ?></textarea></td>
                        </tr>
                        <tr><th scope="row"><label>Disallowed Comment Keys</label></th>
                            <td><textarea name="disallowed_keys" rows="4" class="large-text code" placeholder="When a comment contains any of these words in its content, name, URL, email, or IP, it will be put in the Trash."><?= htmlspecialchars($opt('disallowed_keys', '')) ?></textarea></td>
                        </tr>
                        <tr><th scope="row"><label>Avatars</label></th>
                            <td><label><input name="show_avatars" type="checkbox" value="1"<?= $optChecked('show_avatars', '1') ?> /> Show avatars</label><br />
                                <select name="avatar_rating">
                                    <?php $ar = $opt('avatar_rating', 'G'); ?>
                                    <option value="G"<?= $ar === 'G' ? ' selected' : '' ?>>G &#8212; Suitable for all audiences</option>
                                    <option value="PG"<?= $ar === 'PG' ? ' selected' : '' ?>>PG &#8212; Possibly offensive, usually for audiences 13 and above</option>
                                    <option value="R"<?= $ar === 'R' ? ' selected' : '' ?>>R &#8212; Intended for adult audiences above 17</option>
                                    <option value="X"<?= $ar === 'X' ? ' selected' : '' ?>>X &#8212; Even more mature than above</option>
                                </select><br />
                                <select name="avatar_default">
                                    <?php $ad = $opt('avatar_default', 'mystery'); ?>
                                    <option value="mystery"<?= $ad === 'mystery' ? ' selected' : '' ?>>Mystery Person</option>
                                    <option value="blank"<?= $ad === 'blank' ? ' selected' : '' ?>>Blank</option>
                                    <option value="gravatar_default"<?= $ad === 'gravatar_default' ? ' selected' : '' ?>>Gravatar Logo</option>
                                    <option value="identicon"<?= $ad === 'identicon' ? ' selected' : '' ?>>Identicon (Generated)</option>
                                    <option value="wavatar"<?= $ad === 'wavatar' ? ' selected' : '' ?>>Wavatar (Generated)</option>
                                    <option value="monsterid"<?= $ad === 'monsterid' ? ' selected' : '' ?>>MonsterID (Generated)</option>
                                    <option value="retro"<?= $ad === 'retro' ? ' selected' : '' ?>>Retro (Generated)</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <p class="submit"><input type="submit" class="button button-primary" value="Save Changes" /></p>
                </form>

                <?php elseif ($activeScreen === 'options-media'): ?>
                <form action="" method="post">
                    <table class="form-table">
                        <tr><th scope="row"><label>Thumbnail size</label></th>
                            <td>Width: <input name="thumbnail_size_w" type="number" value="<?= htmlspecialchars($opt('thumbnail_size_w', '150')) ?>" class="small-text" /> Height: <input name="thumbnail_size_h" type="number" value="<?= htmlspecialchars($opt('thumbnail_size_h', '150')) ?>" class="small-text" /></td>
                        </tr>
                        <tr><th scope="row"><label>Medium size</label></th>
                            <td>Max Width: <input name="medium_size_w" type="number" value="<?= htmlspecialchars($opt('medium_size_w', '300')) ?>" class="small-text" /> Max Height: <input name="medium_size_h" type="number" value="<?= htmlspecialchars($opt('medium_size_h', '300')) ?>" class="small-text" /></td>
                        </tr>
                        <tr><th scope="row"><label>Large size</label></th>
                            <td>Max Width: <input name="large_size_w" type="number" value="<?= htmlspecialchars($opt('large_size_w', '1024')) ?>" class="small-text" /> Max Height: <input name="large_size_h" type="number" value="<?= htmlspecialchars($opt('large_size_h', '1024')) ?>" class="small-text" /></td>
                        </tr>
                        <tr><th scope="row"><label>Uploading Files</label></th>
                            <td><label><input name="uploads_use_yearmonth_folders" type="checkbox" value="1"<?= $optChecked('uploads_use_yearmonth_folders', '1') ?> /> Organize my uploads into month- and year-based folders</label></td>
                        </tr>
                    </table>
                    <p class="submit"><input type="submit" class="button button-primary" value="Save Changes" /></p>
                </form>

                <?php elseif ($activeScreen === 'options-permalink'): ?>
                <form action="" method="post">
                    <table class="form-table">
                        <tr><th scope="row"><label>Common Settings</label></th>
                            <td>
                                <?php $ps = $opt('permalink_structure', '/%postname%/'); ?>
                                <label><input type="radio" name="permalink_structure" value=""<?= $ps === '' ? ' checked' : '' ?> /> Plain</label><br />
                                <label><input type="radio" name="permalink_structure" value="/%year%/%monthnum%/%day%/%postname%/"<?= $ps === '/%year%/%monthnum%/%day%/%postname%/' ? ' checked' : '' ?> /> Day and name</label><br />
                                <label><input type="radio" name="permalink_structure" value="/%year%/%monthnum%/%postname%/"<?= $ps === '/%year%/%monthnum%/%postname%/' ? ' checked' : '' ?> /> Month and name</label><br />
                                <label><input type="radio" name="permalink_structure" value="/%postname%/"<?= $ps === '/%postname%/' ? ' checked' : '' ?> /> Post name</label><br />
                                <label><input type="radio" name="permalink_structure" value="custom"<?= !in_array($ps, ['', '/%year%/%monthnum%/%day%/%postname%/', '/%year%/%monthnum%/%postname%/', '/%postname%/'], true) ? ' checked' : '' ?> /> Custom Structure</label><br />
                                <input name="permalink_structure_custom" type="text" value="<?= htmlspecialchars(in_array($ps, ['', '/%year%/%monthnum%/%day%/%postname%/', '/%year%/%monthnum%/%postname%/', '/%postname%/'], true) ? '/%postname%/' : $ps) ?>" class="regular-text code" style="margin-top:4px;" />
                            </td>
                        </tr>
                        <tr><th scope="row"><label>Optional</label></th>
                            <td><input name="category_base" type="text" value="<?= htmlspecialchars($opt('category_base', '')) ?>" class="regular-text code" placeholder="category" /> Category base<br />
                                <input name="tag_base" type="text" value="<?= htmlspecialchars($opt('tag_base', '')) ?>" class="regular-text code" placeholder="tags" style="margin-top:4px;" /> Tag base</td>
                        </tr>
                    </table>
                    <p class="submit"><input type="submit" class="button button-primary" value="Save Changes" /></p>
                </form>

                <?php elseif ($activeScreen === 'options-privacy'): ?>
                <form action="" method="post">
                    <div class="notice notice-info inline"><p>Manage your privacy settings and create a privacy policy page.</p></div>
                    <table class="form-table">
                        <tr><th scope="row"><label>Privacy Policy Page</label></th>
                            <td><input name="wp_page_for_privacy_policy" type="text" value="<?= htmlspecialchars($opt('wp_page_for_privacy_policy', '0')) ?>" class="regular-text" />
                                <p style="font-size:12px;color:#787c82;">Enter the ID of the page to use as your privacy policy.</p></td>
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
