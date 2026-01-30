<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Theme\Engines;

use PrestoWorld\Bridge\WordPress\Theme\Gutenberg\CanvasLoader;

class GutenbergEngine extends \PrestoWorld\Theme\Engines\AbstractEngine
{
    public function load(): void
    {
        $this->boot();

        // Gutenberg logic: use canvas loader
        $loader = new CanvasLoader($this->theme);
        $loader->load();
    }

    public function render(string $view, array $data = []): string
    {
        return "Gutenberg Rendering: " . $view;
    }

    protected function bootEngineHelpers(): void
    {
        // Boot gutenberg engine specific helpers
    }
}
