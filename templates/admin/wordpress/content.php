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
                            <p>Welcome to PrestoWorld. Content from your database will appear here.</p>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($activeScreen === 'posts'): ?>

            <div id="post-body-content">
                <div class="notice notice-info inline">
                    <p>Post management is available through the API. Data from <code>pw_posts</code> will render here.</p>
                </div>
                <div class="tablenav top">
                    <div class="alignleft actions">
                        <select name="cat" id="cat" class="postform">
                            <option value="0">All categories</option>
                        </select>
                        <input type="submit" class="button" value="Filter" />
                    </div>
                    <div class="tablenav-pages one-page">
                        <span class="displaying-num">0 items</span>
                    </div>
                    <br class="clear" />
                </div>
                <table class="wp-list-table widefat fixed striped posts">
                    <thead>
                        <tr>
                            <td id="cb" class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1">Select All</label><input id="cb-select-all-1" type="checkbox" /></td>
                            <th scope="col" id="title" class="manage-column column-title column-primary">Title</th>
                            <th scope="col" id="author" class="manage-column column-author">Author</th>
                            <th scope="col" id="categories" class="manage-column column-categories">Categories</th>
                            <th scope="col" id="date" class="manage-column column-date">Date</th>
                        </tr>
                    </thead>
                    <tbody id="the-list">
                        <tr class="no-items"><td class="colspanchange" colspan="5">No posts found.</td></tr>
                    </tbody>
                </table>
            </div>

        <?php elseif ($activeScreen === 'plugins'): ?>

            <div id="post-body-content">
                <div class="notice notice-info inline">
                    <p>Plugin management is available through the API. Data from <code>plugin_registry</code> will render here.</p>
                </div>
                <table class="wp-list-table widefat fixed striped plugins">
                    <thead>
                        <tr>
                            <td id="cb" class="manage-column column-cb check-column"><input id="cb-select-all-1" type="checkbox" /></td>
                            <th scope="col" id="name" class="manage-column column-name column-primary">Plugin</th>
                            <th scope="col" id="description" class="manage-column column-description">Description</th>
                            <th scope="col" id="status" class="manage-column column-status">Status</th>
                            <th scope="col" id="version" class="manage-column column-version">Version</th>
                        </tr>
                    </thead>
                    <tbody id="the-list">
                        <tr class="no-items"><td class="colspanchange" colspan="5">No plugins installed.</td></tr>
                    </tbody>
                </table>
            </div>

        <?php elseif ($activeScreen === 'settings'): ?>

            <div id="post-body-content">
                <form action="" method="post">
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="site-title">Site Title</label></th>
                            <td><input name="site-title" type="text" id="site-title" value="PrestoWorld" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="site-tagline">Tagline</label></th>
                            <td><input name="site-tagline" type="text" id="site-tagline" value="Digital marketplace platform" class="regular-text" /></td>
                        </tr>
                    </table>
                    <p class="submit"><input type="submit" class="button button-primary" value="Save Changes" /></p>
                </form>
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
