<?php

declare(strict_types=1);

namespace Prestoworld\Bridge\WordPress\Response;

use Witals\Framework\Http\Response;

/**
 * Class WordPressResponseBridge
 * 
 * Main bridge between WordPress output and PrestoWorld Response system.
 * Wraps WordPress execution and ensures proper Response object is returned.
 */
class WordPressResponseBridge
{
    public function __construct(
        protected ResponseInterceptor $interceptor
    ) {}

    /**
     * Execute WordPress code and return PrestoWorld Response
     */
    public function execute(callable $wordpressCode): Response
    {
        // Reset state (important for RoadRunner)
        $this->interceptor->reset();
        
        // Start intercepting
        $this->interceptor->start();
        
        try {
            // Execute WordPress code
            $result = $wordpressCode();
            
            // If WordPress code returns a Response, use it
            if ($result instanceof Response) {
                return $result;
            }
            
            // Otherwise, capture buffered output
            return $this->interceptor->end();
            
        } catch (\Throwable $e) {
            // Clean up on error
            $this->interceptor->reset();
            throw $e;
        }
    }

    /**
     * Execute WooCommerce template and return Response
     */
    public function executeTemplate(string $template, array $data = []): Response
    {
        return $this->execute(function() use ($template, $data) {
            extract($data);
            
            if (file_exists($template)) {
                include $template;
            } else {
                throw new \RuntimeException("Template not found: $template");
            }
        });
    }
}
