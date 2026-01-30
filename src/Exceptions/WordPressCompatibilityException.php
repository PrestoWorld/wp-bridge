<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Exceptions;

use RuntimeException;

class WordPressCompatibilityException extends RuntimeException
{
    public static function undefinedFunction(string $functionName): self
    {
        return new self(
            "WordPress Compatibility Error: The function '{$functionName}' is not compatible or not implemented yet in PrestoWorld Bridge. " .
            "Please check if you can use a native PrestoWorld alternative or request this feature."
        );
    }

    public static function notImplemented(string $feature): self
    {
        return new self("WordPress Compatibility Error: The feature '{$feature}' is not implemented yet.");
    }
}
