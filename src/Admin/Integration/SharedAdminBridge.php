<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Admin\Integration;

use PrestoWorld\Core\Admin\AdminDashboardManager;
use PrestoWorld\Core\Admin\Contracts\AdminPageInterface;
use PrestoWorld\Core\Admin\Registry\AdminPageRegistry;
use Witals\Framework\Contracts\Container;

class SharedAdminBridge
{
    public function __construct(protected Container $container)
    {
    }

    public function hasSharedManager(): bool
    {
        try {
            return $this->container->has(AdminDashboardManager::class) || class_exists(AdminDashboardManager::class);
        } catch (\Throwable $e) {
            return class_exists(AdminDashboardManager::class);
        }
    }

    public function getManager(): ?AdminDashboardManager
    {
        try {
            if ($this->container->has(AdminDashboardManager::class)) {
                return $this->container->make(AdminDashboardManager::class);
            }
        } catch (\Throwable $e) {
        }

        if (class_exists(AdminDashboardManager::class) && function_exists('app')) {
            try {
                return app(AdminDashboardManager::class);
            } catch (\Throwable $e) {
            }
        }

        return null;
    }

    public function getSharedMenuTree(): array
    {
        $manager = $this->getManager();
        if ($manager !== null) {
            try {
                return $manager->getMenuTree();
            } catch (\Throwable $e) {
            }
        }
        return [];
    }

    public function registerPage(AdminPageInterface $page): void
    {
        $manager = $this->getManager();
        if ($manager !== null) {
            try {
                $manager->registerPage($page);
                return;
            } catch (\Throwable $e) {
            }
        }

        try {
            $registry = $this->container->has(AdminPageRegistry::class)
                ? $this->container->make(AdminPageRegistry::class)
                : (function_exists('app') ? app(AdminPageRegistry::class) : null);
            if ($registry instanceof AdminPageRegistry) {
                $registry->registerPage($page);
            }
        } catch (\Throwable $e) {
        }
    }

    public function render(string $slug, array $data = []): mixed
    {
        $manager = $this->getManager();
        if ($manager !== null) {
            try {
                return $manager->render($slug, $data);
            } catch (\Throwable $e) {
            }
        }
        return null;
    }
}
