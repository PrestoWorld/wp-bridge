<?php

declare(strict_types=1);

namespace Prestoworld\Bridge\WordPress\Theme\Gutenberg;

use PrestoWorld\Theme\Theme;

class CanvasLoader
{
    protected Theme $theme;

    public function __construct(Theme $theme)
    {
        $this->theme = $theme;
    }

    public function load(): void
    {
        // Logic to load Gutenberg theme using canvas
    }
}
