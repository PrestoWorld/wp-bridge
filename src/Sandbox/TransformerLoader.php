<?php

declare(strict_types=1);

namespace Prestoworld\Bridge\WordPress\Sandbox;

use Prestoworld\Bridge\WordPress\Sandbox\Transformers\TransformerInterface;

/**
 * Class TransformerLoader
 * 
 * Auto-discovers and loads transformers from:
 * 1. Core transformers (built-in)
 * 2. Plugin manifests (plugin.json or composer.json extra section)
 * 3. User-defined transformers (config/transformers.php)
 */
class TransformerLoader
{
    protected string $pluginsPath;
    protected string $configPath;
    protected ?\Prestoworld\Bridge\WordPress\Sandbox\Services\TransformerRegistryService $registryService = null;

    public function __construct(
        string $pluginsPath,
        string $configPath,
        ?\Prestoworld\Bridge\WordPress\Sandbox\Services\TransformerRegistryService $registryService = null
    ) {
        $this->pluginsPath = $pluginsPath;
        $this->configPath = $configPath;
        $this->registryService = $registryService;
    }

    /**
     * Discover all transformers from various sources
     */
    public function discover(): array
    {
        $transformers = [];
        
        // 1. Load core transformers (always available)
        $transformers = array_merge($transformers, $this->loadCoreTransformers());
        
        // 2. Load from centralized registry (PrestoWorld Transformer Registry)
        // This registry maps plugin signatures to their transformers
        $transformers = array_merge($transformers, $this->loadFromRegistry());
        
        // 3. Load user config overrides
        $transformers = array_merge($transformers, $this->loadUserConfig());
        
        return $transformers;
    }

    protected function loadCoreTransformers(): array
    {
        return [
            [
                'id' => 'global_to_container',
                'class' => \Prestoworld\Bridge\WordPress\Sandbox\Transformers\GlobalToContainerTransformer::class,
                'keywords' => ['global'],
                'enabled' => true
            ],
            [
                'id' => 'wpdb_direct_query',
                'class' => \Prestoworld\Bridge\WordPress\Sandbox\Transformers\WpdbDirectQueryTransformer::class,
                'keywords' => ['$wpdb', 'query'],
                'enabled' => true
            ],
            [
                'id' => 'output_buffer',
                'class' => \Prestoworld\Bridge\WordPress\Sandbox\Transformers\DirectOutputBufferTransformer::class,
                'keywords' => ['echo', 'print', 'wp_die'],
                'enabled' => true
            ],
        ];
    }

    /**
     * Load transformers from centralized registry based on installed plugins
     * 
     * The registry is a mapping file that PrestoWorld maintains:
     * - Detects which plugins are installed (by scanning plugin headers)
     * - Matches plugin slug/version to known transformer packages
     * - Downloads/loads appropriate transformers from PrestoWorld CDN or local cache
     */
    protected function loadFromRegistry(): array
    {
        $transformers = [];
        
        // Get list of installed plugins
        $installedPlugins = $this->getInstalledPlugins();
        
        // Use database service if available
        if ($this->registryService) {
            foreach ($installedPlugins as $plugin) {
                $pluginTransformers = $this->registryService->getForPlugin(
                    $plugin['slug'],
                    $plugin['version']
                );
                $transformers = array_merge($transformers, $pluginTransformers);
            }
        } else {
            // Fallback to built-in registry
            $registry = $this->getBuiltInRegistry();
            
            foreach ($installedPlugins as $plugin) {
                $slug = $plugin['slug'];
                $version = $plugin['version'];
                
                if (isset($registry[$slug])) {
                    foreach ($registry[$slug] as $transformerConfig) {
                        if ($this->matchesVersion($version, $transformerConfig['version_constraint'] ?? '*')) {
                            $transformers[] = [
                                'id' => $transformerConfig['id'],
                                'class' => $transformerConfig['class'],
                                'keywords' => $transformerConfig['keywords'] ?? [],
                                'enabled' => true,
                                'plugin' => $slug
                            ];
                        }
                    }
                }
            }
        }
        
        return $transformers;
    }

    /**
     * Scan wp-content/plugins to detect installed plugins
     */
    protected function getInstalledPlugins(): array
    {
        $plugins = [];
        
        if (!is_dir($this->pluginsPath)) {
            return $plugins;
        }

        foreach (scandir($this->pluginsPath) as $dir) {
            if ($dir === '.' || $dir === '..') continue;
            
            $pluginPath = $this->pluginsPath . '/' . $dir;
            
            // Find main plugin file (*.php with Plugin Name header)
            $mainFile = $this->findMainPluginFile($pluginPath);
            
            if ($mainFile) {
                $headers = $this->parsePluginHeaders($mainFile);
                $plugins[] = [
                    'slug' => $dir,
                    'name' => $headers['Plugin Name'] ?? $dir,
                    'version' => $headers['Version'] ?? '0.0.0',
                    'path' => $pluginPath
                ];
            }
        }
        
        return $plugins;
    }

    protected function findMainPluginFile(string $pluginPath): ?string
    {
        if (!is_dir($pluginPath)) return null;
        
        foreach (glob($pluginPath . '/*.php') as $file) {
            $content = file_get_contents($file, false, null, 0, 8192);
            if (stripos($content, 'Plugin Name:') !== false) {
                return $file;
            }
        }
        
        return null;
    }

    protected function parsePluginHeaders(string $file): array
    {
        $content = file_get_contents($file, false, null, 0, 8192);
        $headers = [];
        
        if (preg_match('/Plugin Name:\s*(.+)/i', $content, $m)) {
            $headers['Plugin Name'] = trim($m[1]);
        }
        if (preg_match('/Version:\s*(.+)/i', $content, $m)) {
            $headers['Version'] = trim($m[1]);
        }
        
        return $headers;
    }


    protected function getBuiltInRegistry(): array
    {
        return [
            'woocommerce' => [
                [
                    'id' => 'wc_orders',
                    'class' => \Prestoworld\Bridge\WordPress\Sandbox\Transformers\WooCommerceOrderTransformer::class,
                    'keywords' => ['update_post_meta', 'wc_get_orders', 'WC_Order'],
                    'version_constraint' => '>=3.0'
                ]
            ],
            // More plugins can be added here or loaded dynamically
        ];
    }

    protected function matchesVersion(string $version, string $constraint): bool
    {
        if ($constraint === '*') return true;
        
        // Simple version comparison (can use composer/semver for complex cases)
        if (str_starts_with($constraint, '>=')) {
            return version_compare($version, substr($constraint, 2), '>=');
        }
        
        return version_compare($version, $constraint, '=');
    }

    protected function loadUserConfig(): array
    {
        $configFile = $this->configPath . '/transformers.php';
        
        if (!file_exists($configFile)) {
            return [];
        }
        
        $config = require $configFile;
        return $config['transformers'] ?? [];
    }
}
