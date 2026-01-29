<?php

declare(strict_types=1);

namespace Prestoworld\Bridge\WordPress\Sandbox\Requirements;

use Prestoworld\Bridge\WordPress\Sandbox\RequirementInterface;
use Prestoworld\Bridge\WordPress\Sandbox\Context;

/**
 * Class PluginRequirement
 * 
 * Requires a specific plugin to be active/present for the transformer to apply.
 * 
 * Example: Only apply WooCommerce-fixer if WooCommerce is the plugin being compiled.
 */
class PluginRequirement implements RequirementInterface
{
    public function __construct(
        protected string $pluginName,
        protected string $versionOperator = '>=', // >=, <=, etc
        protected string $version = '0.0.0'
    ) {}

    public function check(Context $context): bool
    {
        // Simple name check
        if ($context->pluginName !== $this->pluginName) {
            return false;
        }

        // Version check logic (using version_compare)
        if ($this->version !== '0.0.0') {
             return version_compare($context->pluginVersion, $this->version, $this->versionOperator);
        }

        return true;
    }
}
