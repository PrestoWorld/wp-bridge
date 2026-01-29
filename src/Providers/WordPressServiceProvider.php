<?php

declare(strict_types=1);

namespace Prestoworld\Bridge\WordPress\Providers;

use App\Support\ServiceProvider;
use Prestoworld\Bridge\WordPress\WordPressLoader;
use Prestoworld\Bridge\WordPress\WordPressBridge;
use Prestoworld\Bridge\WordPress\Theme\ThemeLoader;
use Prestoworld\Bridge\WordPress\Bootstrap\WordPressConceptBootstrap;

class WordPressServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Load WordPress Helpers
        $helpers = __DIR__ . '/../helpers.php';
        if (file_exists($helpers)) {
            require_once $helpers;
        }

        // Register WordPress Loader
        $this->singleton(WordPressLoader::class, function ($app) {
            return new WordPressLoader($app);
        });

        // Register Settings Registry
        $this->singleton(\Prestoworld\Bridge\WordPress\Settings\SettingsRegistry::class, function ($app) {
            return new \Prestoworld\Bridge\WordPress\Settings\SettingsRegistry();
        });
        
        // Register WordPress Bridge
        $this->singleton(WordPressBridge::class, function ($app) {
            return new WordPressBridge(
                $app,
                $app->make(WordPressLoader::class)
            );
        });

        // Register WordPress Concept Bootstrapper
        $this->app->addBootstrapper(WordPressConceptBootstrap::class);

        // Register Admin Bootstrapper (Not auto-booted, called by Driver)
        $this->singleton(\Prestoworld\Bridge\WordPress\Bootstrap\WordPressAdminBootstrap::class, function ($app) {
            return new \Prestoworld\Bridge\WordPress\Bootstrap\WordPressAdminBootstrap();
        });

        // Register Theme Loader for Zero Migration
        $this->singleton(ThemeLoader::class, function ($app) {
            return new ThemeLoader(
                $app,
                $app->make(\PrestoWorld\Theme\ThemeManager::class)
            );
        });

        // Register WordPress Dispatcher for Native Router Fallback
        $this->singleton(\Prestoworld\Bridge\WordPress\Routing\WordPressDispatcher::class, function ($app) {
            return new \Prestoworld\Bridge\WordPress\Routing\WordPressDispatcher($app);
        });

        // --- OPTIONS ECOSYSTEM ---
        $this->singleton('wp.options', function ($app) {
            return new \Prestoworld\Bridge\WordPress\Options\OptionsManager();
        });

        // --- SANDBOX ECOSYSTEM ---

        // 1. Transformer Registry Service (Database-backed)
        $this->singleton(\Prestoworld\Bridge\WordPress\Sandbox\Services\TransformerRegistryService::class, function ($app) {
            return new \Prestoworld\Bridge\WordPress\Sandbox\Services\TransformerRegistryService(
                $app->make(\Cycle\ORM\EntityManagerInterface::class)
            );
        });

        // 2. Transformer Loader (Auto-discovery)
        $this->singleton(\Prestoworld\Bridge\WordPress\Sandbox\TransformerLoader::class, function ($app) {
            return new \Prestoworld\Bridge\WordPress\Sandbox\TransformerLoader(
                $app->basePath('public/wp-content/plugins'),
                $app->basePath('config'),
                $app->make(\Prestoworld\Bridge\WordPress\Sandbox\Services\TransformerRegistryService::class)
            );
        });

        // 2. Transformer Repository (Dynamic)
        $this->singleton(\Prestoworld\Bridge\WordPress\Sandbox\TransformerRepository::class, function ($app) {
            $loader = $app->make(\Prestoworld\Bridge\WordPress\Sandbox\TransformerLoader::class);
            $repo = new \Prestoworld\Bridge\WordPress\Sandbox\TransformerRepository();
            
            // Auto-discover and register all transformers
            $discovered = $loader->discover();
            
            foreach ($discovered as $config) {
                if (!($config['enabled'] ?? true)) continue;
                
                // Instantiate transformer class
                $transformer = new $config['class']();
                $repo->register($config['id'], $transformer);
            }
            
            return $repo;
        });

        // 2. Transformer Engine (Compiler) with Unified Storage
        $this->singleton(\Prestoworld\Bridge\WordPress\Sandbox\TransformerEngine::class, function ($app) {
            // Reuse the same driver config as HookRegistry
            $driver = env('HOOK_REGISTRY_DRIVER', 'sqlite');
            
            // Get connection based on driver
            $connection = match($driver) {
                'sqlite' => new \PDO('sqlite:' . $app->basePath('storage/framework/presto_cache.sqlite')),
                'redis' => $app->has('redis') ? $app->make('redis') : (function() {
                    $r = new \Redis();
                    $r->connect(env('REDIS_HOST', '127.0.0.1'), (int)env('REDIS_PORT', 6379));
                    return $r;
                })(),
                'mongodb' => new \MongoDB\Client(env('MONGODB_URI', 'mongodb://localhost:27017')),
                default => null
            };
            
            $storage = $connection 
                ? new \Prestoworld\Bridge\WordPress\Sandbox\Storage\UnifiedCacheStorage($driver, $connection)
                : null;
            
            $engine = new \Prestoworld\Bridge\WordPress\Sandbox\TransformerEngine(
                $app->make(\Prestoworld\Bridge\WordPress\Sandbox\TransformerRepository::class),
                $storage
            );
            
            // Auto-indexing from discovered transformers
            $loader = $app->make(\Prestoworld\Bridge\WordPress\Sandbox\TransformerLoader::class);
            $discovered = $loader->discover();
            
            foreach ($discovered as $config) {
                if (!($config['enabled'] ?? true)) continue;
                if (empty($config['keywords'])) continue;
                
                $engine->indexTransformer($config['id'], $config['keywords']);
            }
            
            // Core options indexing
            $engine->indexTransformer('wp_options', [
                'get_option', 'update_option', 'add_option', 'delete_option',
                'get_transient', 'set_transient', 'delete_transient'
            ]);
            
            return $engine;
        });
        
        // 3. Database Proxy
        $this->singleton(\Prestoworld\Bridge\WordPress\Database\WpDbProxy::class, function ($app) {
            return new \Prestoworld\Bridge\WordPress\Database\WpDbProxy($app);
        });

        // 4. Override WPDB global via container alias (if accessed via app)
        $this->app->alias(\Prestoworld\Bridge\WordPress\Database\WpDbProxy::class, 'wpdb');

        // 5. Isolation Sandbox
        $this->singleton(\Prestoworld\Bridge\WordPress\Sandbox\IsolationSandbox::class, function ($app) {
             return new \Prestoworld\Bridge\WordPress\Sandbox\IsolationSandbox(
                 $app->make(\PrestoWorld\Hooks\HookManager::class),
                 $app->make(\PrestoWorld\Admin\AdminManager::class)
             );
        });

        // 6. Autoloader Interceptor (On-the-fly transformation)
        $this->singleton(\Prestoworld\Bridge\WordPress\Sandbox\AutoloaderInterceptor::class, function ($app) {
            $storage = $app->make(\Prestoworld\Bridge\WordPress\Sandbox\TransformerEngine::class)->storage ?? null;
            
            $interceptor = new \Prestoworld\Bridge\WordPress\Sandbox\AutoloaderInterceptor(
                $app->make(\Prestoworld\Bridge\WordPress\Sandbox\TransformerEngine::class),
                $storage
            );
            
            // Mark plugins that need transformation
            $pluginsPath = $app->basePath('public/wp-content/plugins');
            
            if (is_dir($pluginsPath . '/woocommerce')) {
                $interceptor->addTransformablePlugin('woocommerce', $pluginsPath . '/woocommerce');
            }
            
            // Auto-detect other plugins from registry
            $loader = $app->make(\Prestoworld\Bridge\WordPress\Sandbox\TransformerLoader::class);
            $installedPlugins = $loader->discover();
            
            foreach ($installedPlugins as $config) {
                if (isset($config['plugin']) && is_dir($pluginsPath . '/' . $config['plugin'])) {
                    $interceptor->addTransformablePlugin(
                        $config['plugin'],
                        $pluginsPath . '/' . $config['plugin']
                    );
                }
            }
            
            return $interceptor;
        });

        // --- RESPONSE BRIDGE ---

        // 7. Response Interceptor
        $this->singleton(\Prestoworld\Bridge\WordPress\Response\ResponseInterceptor::class, function ($app) {
            return new \Prestoworld\Bridge\WordPress\Response\ResponseInterceptor();
        });

        // 8. WordPress Response Bridge
        $this->singleton(\Prestoworld\Bridge\WordPress\Response\WordPressResponseBridge::class, function ($app) {
            return new \Prestoworld\Bridge\WordPress\Response\WordPressResponseBridge(
                $app->make(\Prestoworld\Bridge\WordPress\Response\ResponseInterceptor::class)
            );
        });
    }

    public function boot(): void
    {
        $themeManager = $this->app->make(\PrestoWorld\Theme\ThemeManager::class);

        // Register WordPress theme discovery path
        $themeManager->addDiscoveryPath($this->app->basePath('public/wp-content/themes'));

        // Register WordPress-specific theme engines (Simulation Mode)
        $themeManager->registerEngine(
            \PrestoWorld\Theme\ThemeType::GUTENBERG->value,
            \Prestoworld\Bridge\WordPress\Theme\Engines\GutenbergEngine::class
        );

        $themeManager->registerEngine(
            \PrestoWorld\Theme\ThemeType::LEGACY->value,
            \Prestoworld\Bridge\WordPress\Theme\Engines\LegacyEngine::class
        );
        
        // Note: WordPressLoader->load() is NOT called. 
        // We are simulating WP behavior natively.

        // Activate Autoloader Interceptor for on-the-fly transformation
        $interceptor = $this->app->make(\Prestoworld\Bridge\WordPress\Sandbox\AutoloaderInterceptor::class);
        $interceptor->register();

        // Register WordPress Admin Driver if AdminManager is available
        if ($this->app->has(\PrestoWorld\Admin\AdminManager::class)) {
            $this->app->make(\PrestoWorld\Admin\AdminManager::class)->registerDriver(
                'wordpress', 
                \Prestoworld\Bridge\WordPress\Dashboard\WordPressDashboardDriver::class
            );
        }
    }
}
