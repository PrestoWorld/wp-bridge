<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Sandbox;

/**
 * Class StreamWrapper
 * 
 * Alternative approach: Use PHP Stream Wrapper to intercept file_get_contents, require, include.
 * This is more aggressive than autoloader interception and catches ALL file loads.
 * 
 * Usage:
 * StreamWrapper::register('woocommerce', '/path/to/woocommerce', $engine);
 * 
 * Then any require/include of woocommerce files will be transformed automatically.
 */
class StreamWrapper
{
    protected static TransformerEngine $engine;
    protected static ?Storage\UnifiedCacheStorage $storage = null;
    protected static array $pluginMappings = [];
    
    public $context;
    protected $handle;
    protected string $path;

    /**
     * Register stream wrapper for a plugin
     */
    public static function register(string $protocol, string $realPath, TransformerEngine $engine, ?Storage\UnifiedCacheStorage $storage = null): void
    {
        self::$engine = $engine;
        self::$storage = $storage;
        self::$pluginMappings[$protocol] = rtrim($realPath, '/');
        
        if (!in_array($protocol, stream_get_wrappers())) {
            stream_wrapper_register($protocol, self::class);
        }
    }

    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        $this->path = $this->resolvePath($path);
        
        if (!file_exists($this->path)) {
            return false;
        }

        // Check if this file needs transformation
        if ($this->needsTransformation($this->path)) {
            $transformed = $this->getTransformed($this->path);
            $this->handle = fopen('php://memory', 'r+');
            fwrite($this->handle, $transformed);
            rewind($this->handle);
        } else {
            $this->handle = fopen($this->path, $mode);
        }

        return $this->handle !== false;
    }

    public function stream_read(int $count): string|false
    {
        return fread($this->handle, $count);
    }

    public function stream_eof(): bool
    {
        return feof($this->handle);
    }

    public function stream_stat(): array|false
    {
        return fstat($this->handle);
    }

    public function stream_close(): void
    {
        if ($this->handle) {
            fclose($this->handle);
        }
    }

    public function url_stat(string $path, int $flags): array|false
    {
        $realPath = $this->resolvePath($path);
        return @stat($realPath);
    }

    protected function resolvePath(string $streamPath): string
    {
        // Convert presto-wc://src/Admin/API.php -> /real/path/woocommerce/src/Admin/API.php
        foreach (self::$pluginMappings as $protocol => $realPath) {
            $prefix = $protocol . '://';
            if (str_starts_with($streamPath, $prefix)) {
                return $realPath . '/' . substr($streamPath, strlen($prefix));
            }
        }
        
        return $streamPath;
    }

    protected function needsTransformation(string $path): bool
    {
        // Only transform .php files
        return str_ends_with($path, '.php');
    }

    protected function getTransformed(string $path): string
    {
        $cacheKey = md5($path . ':' . filemtime($path));

        if (self::$storage && self::$storage->has($cacheKey)) {
            return self::$storage->get($cacheKey);
        }

        $source = file_get_contents($path);
        $transformed = self::$engine->compile($source, $cacheKey);

        if (self::$storage) {
            self::$storage->put($cacheKey, $transformed);
        }

        return $transformed;
    }
}
