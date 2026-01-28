<?php

declare(strict_types=1);

namespace Prestoworld\Bridge\WordPress\Routing;

use Witals\Framework\Application;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use App\Models\Post;
use Cycle\ORM\ORMInterface;
use PrestoWorld\Theme\ThemeManager;

/**
 * WordPress Dispatcher (Zero Migration / Simulation Mode)
 * 
 * Specifically optimized for Post and Page data types.
 */
class WordPressDispatcher
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Dispatch the request by simulating WordPress rewrite logic for Posts and Pages
     */
    public function dispatch(Request $request): Response
    {
        $this->prepareEnvironment($request);

        $path = ltrim($request->path(), '/');
        $orm = $this->app->make(ORMInterface::class);
        $repo = $orm->getRepository(Post::class);

        $post = null;

        // 1. Handle Front Page / Home
        if (empty($path)) {
            // error_log("WordPressDispatcher: Handling home page fallback...");
            // In WP simulation, we look for a page that might be set as front page
            $post = $repo->findOne(['status' => 'publish', 'type' => 'page', 'slug' => 'home']) 
                 ?? $repo->findOne(['status' => 'publish', 'type' => 'page']);
            
            if (!$post) {
                // error_log("WordPressDispatcher: No home page found in database.");
                // Return a special response or fallback? 
                // For now, let's return the first available post if no page exists
                $post = $repo->findOne(['status' => 'publish', 'type' => 'post']);
            }
        } 
        
        // 2. Resolve by Slug (supports both Posts and Pages)
        if (!$post && !empty($path)) {
            $segments = explode('/', rtrim($path, '/'));
            $slug = end($segments);

            // Search for both types in a single query to avoid N+1/Sequential lookups
            // Cycle ORM maps properties (slug, status, type) to columns (post_name, post_status, post_type)
            $post = $repo->select()
                ->where('slug', $slug)
                ->where('status', 'publish')
                ->where('type', 'in', ['page', 'post'])
                ->fetchOne();
        }

        // 3. Handle Numeric ID Fallback (?p=123 for posts, ?page_id=123 for pages)
        if (!$post) {
            $id = $request->query('p') ?: $request->query('page_id');
            if ($id) {
                $post = $repo->findOne(['id' => (int)$id, 'status' => 'publish']);
            }
        }

        // 4. Dispatch to Template Engine
        if (!$post) {
            return $this->handleNotFound();
        }

        return $this->handlePost($post);
    }

    /**
     * Set up environment constants (immutable)
     */
    protected function prepareEnvironment(Request $request): void
    {
        if (!defined('ABSPATH')) {
            define('ABSPATH', $this->app->basePath('public/'));
        }
        if (!defined('WPINC')) {
            define('WPINC', 'wp-includes');
        }
    }

    /**
     * Render the resolved Post or Page
     */
    protected function handlePost(Post $postEntity): Response
    {
        // Set immutable post context (PrestoWorld pattern - no global mutation)
        $GLOBALS['__presto_current_post'] = $postEntity;

        /** @var ThemeManager $themeManager */
        $themeManager = $this->app->make(ThemeManager::class);
        
        // WordPress Template Hierarchy Simulation
        $templates = [];
        
        if ($postEntity->type === 'page') {
            $templates[] = "page-{$postEntity->slug}";
            $templates[] = "page-{$postEntity->id}";
            $templates[] = 'page';
        } elseif ($postEntity->type === 'post') {
            $templates[] = "single-post-{$postEntity->slug}";
            $templates[] = 'single-post';
            $templates[] = 'single';
        } else {
            // Generic custom post types fallback
            $templates[] = "single-{$postEntity->type}";
            $templates[] = 'single';
        }
        
        $templates[] = 'index';

        // Attempt to render the first available template in the hierarchy
        foreach ($templates as $template) {
            try {
                // Set context for Debug Bar
                if ($this->app->has('current_context')) {
                    $this->app->instance('current_context', $template);
                } else {
                    $GLOBALS['__presto_current_context'] = $template;
                }

                $html = $themeManager->render($template, [
                    'post' => $postEntity,
                    'is_page' => ($postEntity->type === 'page'),
                    'is_single' => ($postEntity->type === 'post'),
                    'id' => $postEntity->id
                ]);
                
                return Response::html($html);
            } catch (\Throwable $e) {
                // If template not found, continue to next fallback
                continue;
            }
        }

        // Final fallback if absolutely no templates exist
        return Response::html("<h1>{$postEntity->title}</h1><article>{$postEntity->content}</article>");
    }

    /**
     * Handle 404
     */
    protected function handleNotFound(): Response
    {
        /** @var ThemeManager $themeManager */
        $themeManager = $this->app->make(ThemeManager::class);

        try {
            $html = $themeManager->render('404', ['title' => 'Content Not Found']);
            return Response::html($html, 404);
        } catch (\Throwable $e) {
            return Response::json(['error' => '404 - Not Found'], 404);
        }
    }
}
