<?php

declare(strict_types=1);

namespace Prestoworld\Bridge\WordPress\Theme\Engines;

use Prestoworld\Bridge\WordPress\Theme\Legacy\TemplateLoader;

class LegacyEngine extends \PrestoWorld\Theme\Engines\AbstractEngine
{
    public function load(): void
    {
        $this->boot();

        // Legacy logic: wordpress like template-loader
        $loader = new TemplateLoader($this->theme);
        $loader->load();
    }

    public function render(string $view, array $data = []): string
    {
        // Simple PHP include for legacy themes
        $templatePath = $this->theme->getPath() . '/' . $view . '.php';
        
        if (!file_exists($templatePath)) {
            $templatePath = $this->theme->getPath() . '/index.php';
        }

        if (file_exists($templatePath)) {
            extract($data);
            ob_start();
            include $templatePath;
            return ob_get_clean();
        }

        return "Legacy Template Not Found: " . $view;
    }

    protected function bootEngineHelpers(): void
    {
        // Boot legacy engine specific helpers
    }
}
