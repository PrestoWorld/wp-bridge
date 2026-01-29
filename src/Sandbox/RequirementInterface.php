<?php

declare(strict_types=1);

namespace Prestoworld\Bridge\WordPress\Sandbox;

/**
 * Interface RequirementInterface
 * 
 * Defines a condition that must be met for a Transformer to run.
 * This allows "Conditional Transformers" based on environment,
 * WordPress version, or other active plugins.
 */
interface RequirementInterface
{
    /**
     * Check if the requirement is satisfied.
     * 
     * @param Context $context The current compilation context (file, plugin info, env)
     * @return bool
     */
    public function check(Context $context): bool;
}
