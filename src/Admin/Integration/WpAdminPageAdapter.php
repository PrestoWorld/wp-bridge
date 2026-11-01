<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Admin\Integration;

use PrestoWorld\Core\Admin\Contracts\AdminPageInterface;

class WpAdminPageAdapter implements AdminPageInterface
{
    public function __construct(
        protected string $slug,
        protected string $title,
        protected string $menuTitle,
        protected string $capability = 'manage_options',
        protected ?string $parentSlug = null,
        protected ?string $icon = 'dashicons-admin-generic',
        protected int $position = 10,
        protected $renderCallback = null,
        protected ?string $spaComponent = null,
        protected array $attributes = []
    ) {
    }

    public function getId(): string
    {
        return $this->slug;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getMenuTitle(): string
    {
        return $this->menuTitle;
    }

    public function getCapability(): string
    {
        return $this->capability;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getParentSlug(): ?string
    {
        return $this->parentSlug;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getRenderCallback(): ?callable
    {
        return is_callable($this->renderCallback) ? $this->renderCallback : null;
    }

    public function getSpaComponent(): ?string
    {
        return $this->spaComponent ?? $this->slug;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
