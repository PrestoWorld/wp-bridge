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

$router->group(['prefix' => '/wp-admin'], function () use ($router) {
    $router->get('', \App\Http\Controllers\Admin\SpaController::class);
    $router->get('/', \App\Http\Controllers\Admin\SpaController::class);
    $router->get('/index.php', \App\Http\Controllers\Admin\SpaController::class);
    $router->get('/admin.php', \App\Http\Controllers\Admin\SpaController::class);
    $router->get('/edit.php', \App\Http\Controllers\Admin\SpaController::class);
    $router->get('/plugins.php', \App\Http\Controllers\Admin\SpaController::class);
    $router->get('/options-general.php', \App\Http\Controllers\Admin\SpaController::class);
    $router->get('/themes.php', \App\Http\Controllers\Admin\SpaController::class);
    $router->post('/themes.php', [\App\Http\Controllers\Admin\AdminApiController::class, 'activateThemeFromForm']);
    $router->get('/admin-ajax.php', [\App\Http\Controllers\Admin\SpaController::class, 'adminAjax']);
    $router->post('/admin-ajax.php', [\App\Http\Controllers\Admin\SpaController::class, 'adminAjax']);
});
