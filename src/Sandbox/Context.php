<?php

declare(strict_types=1);

namespace Prestoworld\Bridge\WordPress\Sandbox;

/**
 * Class Context
 * 
 * Holds metadata about the current compilation process.
 */
class Context
{
    public function __construct(
        public string $filePath,
        public string $pluginName = '',
        public string $pluginVersion = '',
        public array $environment = []
    ) {}
}
