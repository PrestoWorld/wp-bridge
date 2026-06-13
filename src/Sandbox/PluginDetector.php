<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Sandbox;

class PluginDetector
{
    /**
     * Standard WordPress plugin headers to detect.
     */
    protected array $headers = [
        'name'        => 'Plugin Name',
        'plugin_uri'  => 'Plugin URI',
        'version'     => 'Version',
        'description' => 'Description',
        'author'      => 'Author',
        'author_uri'  => 'Author URI',
        'text_domain' => 'Text Domain',
        'domain_path' => 'Domain Path',
        'network'     => 'Network',
    ];

    /**
     * Detect if a file is a valid WordPress plugin and return its headers.
     *
     * @param string $file Absolute path to the PHP file.
     * @return array|null Null if not a plugin, array of headers if valid.
     */
    public function detect(string $file): ?array
    {
        if (!file_exists($file)) {
            return null;
        }

        // Only read the first 8KB of the file for performance
        $content = file_get_contents($file, false, null, 0, 8192);
        
        $pluginData = [];
        $isPlugin = false;

        foreach ($this->headers as $field => $regex) {
            if (preg_match('/^[ \t\/*#@]*' . preg_quote($regex, '/') . ':(.*)$/mi', $content, $match)) {
                $pluginData[$field] = trim($match[1]);
                if ($field === 'name') {
                    $isPlugin = true;
                }
            }
        }

        return $isPlugin ? $pluginData : null;
    }

    /**
     * Scan a directory for all valid WordPress plugins.
     *
     * @param string $directory
     * @return array<string, array> Map of file path to plugin data.
     */
    public function scan(string $directory): array
    {
        $plugins = [];
        $directory = rtrim($directory, '/');

        if (!is_dir($directory)) {
            return [];
        }

        foreach (new \DirectoryIterator($directory) as $info) {
            if ($info->isDot()) continue;

            if ($info->isDir()) {
                // Look for a PHP file with the same name as the directory
                $mainFile = $info->getPathname() . '/' . $info->getFilename() . '.php';
                if (file_exists($mainFile)) {
                    if ($data = $this->detect($mainFile)) {
                        $plugins[$mainFile] = $data;
                    }
                } else {
                    // Fallback: scan all PHP files in the root of the plugin directory
                    foreach (new \GlobIterator($info->getPathname() . '/*.php') as $file) {
                        if ($data = $this->detect($file->getPathname())) {
                            $plugins[$file->getPathname()] = $data;
                        }
                    }
                }
            } elseif ($info->getExtension() === 'php') {
                if ($data = $this->detect($info->getPathname())) {
                    $plugins[$info->getPathname()] = $data;
                }
            }
        }

        return $plugins;
    }
}
