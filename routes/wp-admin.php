<?php

declare(strict_types=1);

/**
 * WordPress-style admin URL rewrites.
 * All routes are handled by the router — no actual wp-admin/*.php files.
 */

use PrestoWorld\Bridge\WordPress\Admin\Controllers\WpAdminController;
use App\Http\Controllers\Admin\AdminApiController;
use App\Http\Controllers\Admin\SettingsController;

/** @var \App\Http\Routing\Contracts\RouterInterface $router */

$router->get('/wp-admin', function () {
    header('Location: /wp-admin/');
    exit;
});

$router->group(['prefix' => '/wp-admin'], function () use ($router) {
    // Dashboard
    $router->get('', WpAdminController::class);
    $router->get('/', WpAdminController::class);
    $router->get('/index.php', WpAdminController::class);
    $router->get('/admin.php', WpAdminController::class);

    // Posts
    $router->get('/edit.php', WpAdminController::class);
    $router->get('/post-new.php', WpAdminController::class);
    $router->post('/post-new.php', [\App\Http\Controllers\Admin\PostsController::class, 'savePost']);
    $router->get('/post.php', WpAdminController::class);
    $router->post('/post.php', [\App\Http\Controllers\Admin\PostsController::class, 'updatePost']);

    // Media
    $router->get('/upload.php', WpAdminController::class);
    $router->get('/media-new.php', WpAdminController::class);
    $router->post('/media-upload.php', function () {
        $file = $_FILES['file'] ?? null;
        if ($file === null || $file['error'] !== UPLOAD_ERR_OK) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'No file uploaded or upload error']);
            exit;
        }
        $now = new \DateTimeImmutable();
        $year = $now->format('Y');
        $month = $now->format('m');
        $subDir = "{$year}/{$month}";
        $basePath = defined('PW_BASE_PATH') ? PW_BASE_PATH : getcwd();
        $storageDir = $basePath . '/storage/uploads/' . $subDir;
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }
        $origName = basename($file['name']);
        $destPath = $storageDir . '/' . $origName;
        $counter = 1;
        while (file_exists($destPath)) {
            $info = pathinfo($origName);
            $destPath = $storageDir . '/' . $info['filename'] . '-' . $counter . '.' . ($info['extension'] ?? '');
            $counter++;
        }
        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Failed to save file']);
            exit;
        }
        $filename = basename($destPath);
        $relativePath = $subDir . '/' . $filename;
        $url = '/storage/uploads/' . $relativePath;
        header('Content-Type: application/json');
        echo json_encode(['url' => $url, 'filename' => $filename]);
        exit;
    });

    // Pages
    $router->get('/edit-pages.php', WpAdminController::class);

    // Comments
    $router->get('/edit-comments.php', WpAdminController::class);

    // Appearance
    $router->get('/themes.php', WpAdminController::class);
    $router->post('/themes.php', [AdminApiController::class, 'activateThemeFromForm']);
    $router->get('/widgets.php', WpAdminController::class);
    $router->get('/nav-menus.php', WpAdminController::class);
    $router->get('/customize.php', WpAdminController::class);
    $router->get('/theme-editor.php', WpAdminController::class);

    // Plugins
    $router->get('/plugins.php', WpAdminController::class);
    $router->get('/plugin-install.php', WpAdminController::class);
    $router->get('/plugin-editor.php', WpAdminController::class);

    // Users
    $router->get('/users.php', WpAdminController::class);
    $router->get('/user-new.php', WpAdminController::class);
    $router->get('/user-edit.php', WpAdminController::class);
    $router->get('/profile.php', WpAdminController::class);

    // Tools
    $router->get('/tools.php', WpAdminController::class);
    $router->get('/import.php', WpAdminController::class);
    $router->get('/export.php', WpAdminController::class);
    $router->get('/site-health.php', WpAdminController::class);
    $router->get('/site-health-info.php', WpAdminController::class);

    // Settings
    $router->get('/options-general.php', WpAdminController::class);
    $router->post('/options-general.php', [SettingsController::class, 'saveGeneral']);
    $router->get('/options-writing.php', WpAdminController::class);
    $router->post('/options-writing.php', [SettingsController::class, 'saveWriting']);
    $router->get('/options-reading.php', WpAdminController::class);
    $router->post('/options-reading.php', [SettingsController::class, 'saveReading']);
    $router->get('/options-discussion.php', WpAdminController::class);
    $router->post('/options-discussion.php', [SettingsController::class, 'saveDiscussion']);
    $router->get('/options-media.php', WpAdminController::class);
    $router->post('/options-media.php', [SettingsController::class, 'saveMedia']);
    $router->get('/options-permalink.php', WpAdminController::class);
    $router->post('/options-permalink.php', [SettingsController::class, 'savePermalink']);
    $router->get('/options-privacy.php', WpAdminController::class);
    $router->post('/options-privacy.php', [SettingsController::class, 'savePrivacy']);

    // Updates
    $router->get('/update-core.php', WpAdminController::class);

    // AJAX
    $router->get('/admin-ajax.php', [WpAdminController::class, 'adminAjax']);
    $router->post('/admin-ajax.php', [WpAdminController::class, 'adminAjax']);
});
