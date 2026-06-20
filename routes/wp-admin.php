<?php

declare(strict_types=1);

/**
 * WordPress-style admin URL rewrites.
 * All routes are handled by the router — no actual wp-admin/*.php files.
 */

/** @var \App\Http\Routing\Contracts\RouterInterface $router */

$router->get('/wp-admin', function () {
    header('Location: /wp-admin/');
    exit;
});

$router->get('/wp-admin/', \App\Http\Controllers\Admin\SpaController::class);
$router->get('/wp-admin/index.php', \App\Http\Controllers\Admin\SpaController::class);
$router->get('/wp-admin/admin.php', \App\Http\Controllers\Admin\SpaController::class);
$router->get('/wp-admin/edit.php', \App\Http\Controllers\Admin\SpaController::class);
$router->get('/wp-admin/plugins.php', \App\Http\Controllers\Admin\SpaController::class);
$router->get('/wp-admin/options-general.php', \App\Http\Controllers\Admin\SpaController::class);
$router->get('/wp-admin/admin-ajax.php', [\App\Http\Controllers\Admin\SpaController::class, 'adminAjax']);
$router->post('/wp-admin/admin-ajax.php', [\App\Http\Controllers\Admin\SpaController::class, 'adminAjax']);
