<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Admin\Skins;

use PrestoWorld\Contracts\Admin\SkinInterface;
use Witals\Framework\Contracts\View\Factory as ViewFactory;

class WordPressSkin implements SkinInterface
{
    protected ViewFactory $view;
    protected string $namespace = 'wp-admin';

    public function __construct(ViewFactory $view)
    {
        $this->view = $view;
        // The ViewManager should have the namespace 'wp-admin' registered to the templates directory
    }

    public function getName(): string
    {
        return 'wordpress-classic';
    }

    public function renderLayout(string $content, array $args = []): string
    {
        return (string) $this->view->make("{$this->namespace}::layout", array_merge($args, [
            'context' => $content
        ]));
    }

    public function renderComponent(string $component, array $props = []): string
    {
        return (string) $this->view->make("{$this->namespace}::components.{$component}", $props);
    }

    public function getAssets(): array
    {
        return [
            'css' => [
                'https://s.w.org/wp-includes/css/dashicons.min.css',
                'https://s.w.org/wp-admin/css/common.min.css',
                'https://s.w.org/wp-admin/css/forms.min.css',
                'https://s.w.org/wp-admin/css/admin-menu.min.css',
            ],
            'js' => [],
        ];
    }
}
