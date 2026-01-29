<?php

declare(strict_types=1);

namespace Prestoworld\Bridge\WordPress\Sandbox;

/**
 * Class WordPressModuleLoader
 * 
 * Responsible for loading non-class files (plugins, themes, mu-plugins)
 * through the Sandbox and Transformer engine.
 */
class WordPressModuleLoader
{
    public function __construct(
        protected TransformerEngine $engine,
        protected IsolationSandbox $sandbox,
        protected AutoloaderInterceptor $autoloader
    ) {}

    /**
     * Load all MU-Plugins
     */
    public function loadMuPlugins(string $path): void
    {
        if (!is_dir($path)) return;

        foreach (glob($path . '/*.php') as $file) {
            $this->loadAnyFile($file, $path);
        }
    }

    /**
     * Load active plugins
     */
    public function loadPlugins(string $path, array $activePlugins = []): void
    {
        if (!is_dir($path)) return;

        foreach ($activePlugins as $plugin) {
            $mainFile = $path . '/' . $plugin;
            if (file_exists($mainFile)) {
                $this->loadAnyFile($mainFile, dirname($mainFile), dirname($plugin));
            }
        }
    }

    /**
     * Load active theme
     */
    public function loadTheme(string $path, string $themeSlug): void
    {
        $themePath = $path . '/' . $themeSlug;
        if (!is_dir($themePath)) return;

        $functionsFile = $themePath . '/functions.php';
        if (file_exists($functionsFile)) {
            $this->loadAnyFile($functionsFile, $themePath, $themeSlug);
        }
    }

    /**
     * Universal loader that decides between Direct Load and Sandboxed Load
     */
    protected function loadAnyFile(string $filePath, string $basePath, string $slug = ''): void
    {
        if ($this->isNativePath($filePath)) {
            // Native code: Direct load, no overhead, full access
            require_once $filePath;
        } else {
            // Legacy code: Autoloader + Transformer + Isolation
            if ($slug) {
                $this->autoloader->addTransformablePlugin($slug, $basePath);
            }
            $this->loadSandboxedFile($filePath);
        }
    }

    /**
     * Check if a path belongs to Native PrestoWorld directories
     * (outside of public/wp-content)
     */
    protected function isNativePath(string $path): bool
    {
        // Pure path-based detection for security and performance
        $publicPath = '/public/wp-content/';
        return strpos($path, $publicPath) === false;
    }

    /**
     * The core magic: Transform code and execute in IsolationSandbox
     */
    protected function loadSandboxedFile(string $filePath): void
    {
        $cacheKey = md5($filePath . ':' . filemtime($filePath));
        
        $this->sandbox->run(function() use ($filePath, $cacheKey) {
            // Get transformed code (compiled path)
            $source = file_get_contents($filePath);
            $transformed = $this->engine->compile($source, $cacheKey);
            
            // Execute the compiled file
            $compiledPath = $this->engine->storage->getPath($cacheKey);
            
            // Ensure global scope for plugin/theme definitions
            require_once $compiledPath;
        });
    }
}
