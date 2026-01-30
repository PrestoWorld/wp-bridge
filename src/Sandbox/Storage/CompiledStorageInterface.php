<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Sandbox\Storage;

/**
 * Interface CompiledStorageInterface
 * 
 * Defines how compiled/transformed code is stored and retrieved.
 * Supports horizontal scaling by allowing backends like Redis or S3,
 * combined with local ephermal storage for execution.
 */
interface CompiledStorageInterface
{
    public function has(string $key): bool;
    
    public function get(string $key): ?string;
    
    public function put(string $key, string $code): void;
    
    /**
     * Get a local executable file path for the compiled code.
     * If using a distributed store, this MUST hydration the code to a local temp file.
     */
    public function getPath(string $key): string;
}
