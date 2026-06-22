<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress;

use Witals\Framework\Support\ServiceProvider;
use App\Http\Routing\Contracts\RouterInterface;

class BridgeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // No bindings to register here.
    }

    public function boot(): void
    {
        /** @var RouterInterface $router */
        $router = $this->app->make(RouterInterface::class);
        $router->loadRoutesFrom(__DIR__ . '/../routes/wp-admin.php');
    }
}
