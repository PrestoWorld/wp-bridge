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
    public function execute(callable|object $handler): Response
    {
        // 1. If it's a Native Component, call handle() directly
        if ($handler instanceof \Prestoworld\Bridge\WordPress\Contracts\NativeComponentInterface) {
            return $handler->handle('render');
        }

        // 2. If it's a Closure/Callable, use traditional interception
        $this->interceptor->reset();
        $this->interceptor->start();
        
        try {
            $result = is_callable($handler) ? $handler() : null;
            
            if ($result instanceof Response) {
                $this->interceptor->reset(); // Don't need buffer if we have Response
                return $result;
            }
            
            return $this->interceptor->end();
        } catch (\Throwable $e) {
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
