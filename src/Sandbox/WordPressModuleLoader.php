<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Sandbox;

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
            $pluginPath = $path . '/' . dirname($plugin);
            $mainFile = $path . '/' . $plugin;

            // Check if it's a native structured plugin (e.g. plugin-slug/bootstrap.php)
            $bootstrapFile = $path . '/' . $plugin . '/bootstrap.php';
            if ($this->isNativePath($path) && file_exists($bootstrapFile)) {
                $component = require_once $bootstrapFile;
                if ($component instanceof \PrestoWorld\Bridge\WordPress\Contracts\NativeComponentInterface) {
                    $component->boot();
                }
                continue;
            }
            
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
        error_log("ModuleLoader: Attempting to load theme '{$themeSlug}' from '{$path}'");
        $themePath = $path . '/' . $themeSlug;
        if (!is_dir($themePath)) return;

        // Structured Native Theme
        $themeClassFile = $themePath . '/Theme.php';
        if ($this->isNativePath($themePath) && file_exists($themeClassFile)) {
            require_once $themeClassFile;
            $class = "Themes\\" . str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $themeSlug))) . "\\Theme";
            error_log("ModuleLoader: Construced native theme class: {$class}");
            if (class_exists($class)) {
                $theme = new $class();
                error_log("ModuleLoader: Successfully instantiated native theme class: {$class}");
                if ($theme instanceof \PrestoWorld\Bridge\WordPress\Contracts\NativeComponentInterface) {
                    $theme->boot();
                    // Store in container for later usage in ResponseBridge
                    app()->instance('wp.native_theme', $theme);
                } else {
                    error_log("ModuleLoader: Class {$class} does NOT implement NativeComponentInterface");
                }
                return;
            } else {
                error_log("ModuleLoader: Class {$class} NOT found after loading {$themeClassFile}");
            }
        }

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
            $storage = $this->engine->getStorage();
            if ($storage) {
                $compiledPath = $storage->getPath($cacheKey);
                require_once $compiledPath;
            } else {
                eval('?>' . $transformed);
            }
        });

        // Promote captured hooks to PrestoWorld
        $this->sandbox->resolve();
    }
}
