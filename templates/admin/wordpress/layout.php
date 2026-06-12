<div id="wpwrap">
    <div id="adminmenumain" role="navigation" aria-label="Main menu">
        <div id="adminmenuwrap">
            <ul id="adminmenu">
                <li class="wp-menu-separator" role="presentation"><div class="separator"></div></li>
            </ul>
        </div>
    </div>
    <div id="wpcontent">
        <div id="wpbody" role="main">
            <div id="wpbody-content">
                <div class="wrap">
                    <h1 class="wp-heading-inline"><?php echo $title ?? ''; ?></h1>
                    <hr class="wp-header-end">
                    <div id="poststuff">
                        <div id="post-body" class="metabox-holder columns-2">
                            <div id="post-body-content">
                                <?php echo $content; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
