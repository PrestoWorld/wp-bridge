<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Sandbox;

use PrestoWorld\Bridge\WordPress\Sandbox\Storage\UnifiedCacheStorage;

/**
 * Class AutoloaderInterceptor
 * 
 * Intercepts PHP's autoloader to transform WordPress plugin files on-the-fly.
 * This allows us to transform thousands of files without pre-compilation.
 * 
 * Workflow:
 * 1. Register custom autoloader BEFORE WordPress loads
 * 2. When WooCommerce tries to load a class, intercept it
 * 3. Check if file needs transformation (based on plugin slug)
 * 4. Transform & cache the file
 * 5. Include the transformed version
 */
class AutoloaderInterceptor
{
    protected TransformerEngine $engine;
    protected ?UnifiedCacheStorage $storage;
    protected array $pluginPaths = [];
    protected array $transformablePlugins = [];

    public function __construct(
        TransformerEngine $engine,
        ?UnifiedCacheStorage $storage = null
    ) {
        $this->engine = $engine;
        $this->storage = $storage;
    }

    /**
     * Register this interceptor as the primary autoloader
     */
    public function register(): void
    {
        spl_autoload_register([$this, 'autoload'], true, true); // Prepend = true
    }

    /**
     * Mark a plugin as transformable
     */
    public function addTransformablePlugin(string $slug, string $path): void
    {
        $this->transformablePlugins[$slug] = true;
        $this->pluginPaths[$slug] = rtrim($path, '/');
    }

    /**
     * Add an entire directory of plugins/themes to be transformable
     */
    public function addTransformableDirectory(string $basePath): void
    {
        if (!is_dir($basePath)) return;

        foreach (scandir($basePath) as $dir) {
            if ($dir === '.' || $dir === '..') continue;
            
            $fullPath = $basePath . '/' . $dir;
            if (is_dir($fullPath)) {
                $this->addTransformablePlugin($dir, $fullPath);
            } elseif (is_file($fullPath) && str_ends_with($fullPath, '.php')) {
                // For single file plugins (common in mu-plugins)
                $this->addTransformablePlugin($dir, $basePath);
            }
        }
    }

    /**
     * Autoload handler
     */
    public function autoload(string $class): void
    {
        // Try to find which plugin this class belongs to
        foreach ($this->pluginPaths as $slug => $path) {
            if (!isset($this->transformablePlugins[$slug])) continue;

            // Convert class name to file path
            $possibleFile = $this->classToFile($class, $path);
            
            if ($possibleFile && file_exists($possibleFile)) {
                $this->loadTransformed($possibleFile, $slug);
                return;
            }
        }
    }

    /**
     * Load a file with transformation
     */
    protected function loadTransformed(string $filePath, string $pluginSlug): void
    {
        // Generate cache key based on file path + mtime
        $cacheKey = $this->getCacheKey($filePath);

        // Check if already compiled
        if ($this->storage && $this->storage->has($cacheKey)) {
            // Load from cache
            $compiledPath = $this->storage->getPath($cacheKey);
            require_once $compiledPath;
            return;
        }

        // Read source
        $source = file_get_contents($filePath);

        // Transform
        $transformed = $this->engine->compile($source, $cacheKey);

        // Cache it
        if ($this->storage) {
            $this->storage->put($cacheKey, $transformed);
            $compiledPath = $this->storage->getPath($cacheKey);
            require_once $compiledPath;
        } else {
            // No cache, eval directly (not recommended for production)
            eval('?>' . $transformed);
        }
    }

    /**
     * Convert class name to file path
     */
    protected function classToFile(string $class, string $basePath): ?string
    {
        // Handle PSR-4 style
        // Example: WooCommerce\Admin\API\Reports -> woocommerce/src/Admin/API/Reports.php
        
        $relativePath = str_replace('\\', '/', $class) . '.php';
        
        // Try common patterns
        $patterns = [
            $basePath . '/src/' . $relativePath,
            $basePath . '/includes/' . $relativePath,
            $basePath . '/' . $relativePath,
            $basePath . '/lib/' . $relativePath,
        ];

        foreach ($patterns as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Generate cache key for a file
     */
    protected function getCacheKey(string $filePath): string
    {
        // Use file path + mtime for cache invalidation
        $mtime = filemtime($filePath);
        return md5($filePath . ':' . $mtime);
    }
}
