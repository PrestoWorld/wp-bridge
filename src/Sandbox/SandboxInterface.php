<?php

declare(strict_types=1);

namespace Prestoworld\Bridge\WordPress\Sandbox;

/**
 * Interface SandboxInterface
 * 
 * Defines the contract for an isolated WordPress execution environment.
 * This sandbox intercepts WordPress procedural calls and translates them
 * into PrestoWorld framework actions, ensuring legacy code doesn't pollute
 * the modern application state.
 */
interface SandboxInterface
{
    /**
     * Isolate a closure execution within the sandbox.
     * 
     * @param callable $callback The WordPress code to execute
     * @param array $context Initial context/globals for the sandbox
     * @return mixed Result of the execution
     */
    public function run(callable $callback, array $context = []);

    /**
     * Capture WordPress hooks registered during the sandbox session.
     * 
     * @return array List of hooks (actions/filters) captured
     */
    public function captureHooks(): array;

    /**
     * Resolve and translate captured state to PrestoWorld.
     * 
     * @return void
     */
    public function resolve(): void;
}
