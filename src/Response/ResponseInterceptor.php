<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Response;

use Witals\Framework\Http\Response;

/**
 * Class ResponseInterceptor
 * 
 * Intercepts WordPress output and converts it to PrestoWorld Response objects.
 * This ensures WordPress plugins (like WooCommerce) can work seamlessly with
 * RoadRunner/Swoole without breaking the response lifecycle.
 * 
 * Handles:
 * - Output buffering (echo, print)
 * - wp_die() / wp_redirect()
 * - HTTP headers (header(), status codes)
 * - JSON responses (wp_send_json)
 */
class ResponseInterceptor
{
    protected array $capturedHeaders = [];
    protected int $statusCode = 200;
    protected ?string $redirectUrl = null;
    protected bool $isBuffering = false;

    /**
     * Start intercepting WordPress output
     */
    public function start(): void
    {
        if ($this->isBuffering) {
            return;
        }

        // Start output buffering
        ob_start([$this, 'captureOutput']);
        
        // Override header() function via stream wrapper or global override
        $this->overrideHeaderFunction();
        
        $this->isBuffering = true;
    }

    /**
     * Stop intercepting and return PrestoWorld Response
     */
    public function end(): Response
    {
        if (!$this->isBuffering) {
            return Response::make('', 200);
        }

        // Get buffered content
        $content = ob_get_clean();
        $this->isBuffering = false;

        // Allow post-processing of HTML via Presto Hook system
        if (app()->has(\PrestoWorld\Hooks\HookManager::class)) {
            $content = app()->make(\PrestoWorld\Hooks\HookManager::class)
                ->applyFilters('presto.response_body', $content);
        }

        // Handle redirect
        if ($this->redirectUrl) {
            return Response::redirect($this->redirectUrl, $this->statusCode);
        }

        // Create Response with captured headers
        $response = Response::make($content, $this->statusCode);
        
        foreach ($this->capturedHeaders as $name => $value) {
            $response->header($name, $value);
        }

        return $response;
    }

    /**
     * Capture output buffer callback
     */
    public function captureOutput(string $buffer): string
    {
        // Don't output directly, store for later
        return '';
    }

    /**
     * Override WordPress header functions
     */
    protected function overrideHeaderFunction(): void
    {
        // Register shutdown function to capture headers_list()
        register_shutdown_function(function() {
            if (!$this->isBuffering) return;
            
            foreach (headers_list() as $header) {
                $this->parseHeader($header);
            }
        });
    }

    /**
     * Parse header string
     */
    protected function parseHeader(string $header): void
    {
        // Status header
        if (preg_match('/^HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
            $this->statusCode = (int)$matches[1];
            return;
        }

        // Location header (redirect)
        if (preg_match('/^Location:\s*(.+)$/i', $header, $matches)) {
            $this->redirectUrl = trim($matches[1]);
            $this->statusCode = $this->statusCode === 200 ? 302 : $this->statusCode;
            return;
        }

        // Regular header
        if (strpos($header, ':') !== false) {
            [$name, $value] = explode(':', $header, 2);
            $this->capturedHeaders[trim($name)] = trim($value);
        }
    }

    /**
     * Intercept wp_die()
     */
    public function interceptWpDie(string $message, string $title = '', array $args = []): Response
    {
        $statusCode = $args['response'] ?? 500;
        
        // Create error response
        return Response::make($message, $statusCode)
            ->header('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * Intercept wp_send_json()
     */
    public function interceptWpSendJson($data, int $statusCode = 200): Response
    {
        return Response::json($data, $statusCode);
    }

    /**
     * Reset state for next request (important for long-running processes)
     */
    public function reset(): void
    {
        $this->capturedHeaders = [];
        $this->statusCode = 200;
        $this->redirectUrl = null;
        $this->isBuffering = false;
        
        // Clean any remaining buffers
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
}
