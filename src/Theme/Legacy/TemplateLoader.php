<?php

declare(strict_types=1);

namespace Prestoworld\Bridge\WordPress\Theme\Legacy;

use PrestoWorld\Theme\Theme;

class TemplateLoader
{
    protected Theme $theme;

    public function __construct(Theme $theme)
    {
        $this->theme = $theme;
    }

    public function load(): void
    {
        // Logic to load Legacy theme using template-loader.php (WordPress like)
        $loaderPath = $this->theme->getPath() . '/template-loader.php';
        if (file_exists($loaderPath)) {
            require $loaderPath;
        }
    }
}
