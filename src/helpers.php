<?php

use PrestoWorld\Hooks\HookManager;
use PrestoWorld\Contracts\Hooks\HookStateType;

if (!function_exists('add_filter')) {
    function add_filter($hook_name, $callback, $priority = 10, $accepted_args = 1, HookStateType $stateType = HookStateType::SCOPED) {
        $hooks = app(HookManager::class);
        $hooks->addFilter($hook_name, $callback, $priority, $stateType);
        return true;
    }
}

if (!function_exists('add_action')) {
    function add_action($hook_name, $callback, $priority = 10, $accepted_args = 1, HookStateType $stateType = HookStateType::SCOPED) {
        $hooks = app(HookManager::class);
        $hooks->addAction($hook_name, $callback, $priority, $stateType);
        return true;
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($hook_name, $value, ...$args) {
        $hooks = app(HookManager::class);
        return $hooks->applyFilters($hook_name, $value, ...$args);
    }
}

if (!function_exists('do_action')) {
    function do_action($hook_name, ...$args) {
        $hooks = app(HookManager::class);
        $hooks->doAction($hook_name, ...$args);
    }
}

if (!function_exists('do_action_ref_array')) {
    /**
     * Execute functions hooked on a specific action hook, specifying arguments in an array.
     * 
     * In PrestoWorld, this is treated as a potential Shared State interaction point.
     * Listeners should be registered with HookStateType::SHARED to fully utilize state persistence
     * across async boundaries using this invocation.
     */
    function do_action_ref_array($hook_name, $args) {
        $hooks = app(HookManager::class);
        
        // Native WP passes by reference. In our SyncDispatcher, it works naturally.
        // In Async (Swoole Task), we rely on HookStateType::SHARED mechanisms.
        $hooks->doAction($hook_name, ...$args);
    }
}

if (!function_exists('remove_filter')) {
    function remove_filter($hook_name, $callback, $priority = 10) {
        $hooks = app(HookManager::class);
        $hooks->removeFilter($hook_name, $callback, $priority);
        return true; 
    }
}

if (!function_exists('remove_action')) {
    function remove_action($hook_name, $callback, $priority = 10) {
        $hooks = app(HookManager::class);
        $hooks->removeAction($hook_name, $callback, $priority);
        return true;
    }
}

if (!function_exists('remove_all_filters')) {
    function remove_all_filters($hook_name, $priority = false) {
        $hooks = app(HookManager::class);
        $hooks->removeAllFilters($hook_name, $priority);
        return true;
    }
}

if (!function_exists('remove_all_actions')) {
    function remove_all_actions($hook_name, $priority = false) {
        $hooks = app(HookManager::class);
        $hooks->removeAllActions($hook_name, $priority);
        return true;
    }
}

// --- WordPress Simulation Helpers ---

if (!function_exists('is_404')) {
    function is_404() {
        return http_response_code() === 404;
    }
}

if (!function_exists('get_header')) {
    function get_header($name = null, $args = array()) {
        do_action('get_header', $name, $args);
        
        $themeManager = app(\PrestoWorld\Theme\ThemeManager::class);
        $template = $name ? "header-{$name}" : "header";
        
        try {
            echo $themeManager->render($template, $args);
        } catch (\Throwable $e) {
            // Fallback for simulation if header template not found
        }
    }
}

if (!function_exists('get_footer')) {
    function get_footer($name = null, $args = array()) {
        do_action('get_footer', $name, $args);
        
        $themeManager = app(\PrestoWorld\Theme\ThemeManager::class);
        $template = $name ? "footer-{$name}" : "footer";
        
        try {
            echo $themeManager->render($template, $args);
        } catch (\Throwable $e) {
            // Fallback
        }
    }
}

if (!function_exists('have_posts')) {
    function have_posts() {
        // In immutable context, we check if post data exists in current scope
        return isset($GLOBALS['__presto_current_post']) && !empty($GLOBALS['__presto_current_post']);
    }
}

if (!function_exists('the_post')) {
    function the_post() {
        // In a simulation, we usually only have one post in the loop for now
        do_action('the_post');
    }
}

if (!function_exists('the_title')) {
    function the_title($before = '', $after = '', $display = true) {
        $post = $GLOBALS['__presto_current_post'] ?? null;
        if (!$post) return '';
        
        $title = apply_filters('the_title', $post->title ?? '', $post->id ?? 0);
        if ($display) {
            echo $before . $title . $after;
        } else {
            return $before . $title . $after;
        }
    }
}

if (!function_exists('the_content')) {
    function the_content($more_link_text = null, $strip_teaser = false) {
        $post = $GLOBALS['__presto_current_post'] ?? null;
        if (!$post) return;
        
        $content = apply_filters('the_content', $post->content ?? '');
        echo $content;
    }
}

if (!function_exists('is_page')) {
    function is_page($page = '') {
        $post = $GLOBALS['__presto_current_post'] ?? null;
        if (!$post || $post->type !== 'page') return false;
        if (empty($page)) return true;
        return ($post->slug === $page || $post->id == $page || $post->title === $page);
    }
}

if (!function_exists('is_single')) {
    function is_single($post_val = '') {
        $post = $GLOBALS['__presto_current_post'] ?? null;
        if (!$post || $post->type !== 'post') return false;
        if (empty($post_val)) return true;
        return ($post->slug === $post_val || $post->id == $post_val || $post->title === $post_val);
    }
}

if (!function_exists('get_the_ID')) {
    function get_the_ID() {
        $post = $GLOBALS['__presto_current_post'] ?? null;
        return $post->id ?? 0;
    }
}

if (!function_exists('home_url')) {
    function home_url($path = '') {
        $baseUrl = rtrim(env('APP_URL', 'http://prestoworld.localhost'), '/');
        return $baseUrl . '/' . ltrim($path, '/');
    }
}

if (!function_exists('get_permalink')) {
    function get_permalink($post_id = 0) {
        $post_obj = null;
        if (!$post_id) {
            global $post;
            $post_obj = $post;
        } else {
            $orm = app(\Cycle\ORM\ORMInterface::class);
            $repo = $orm->getRepository(\App\Models\Post::class);
            $post_obj = $repo->findOne(['id' => (int)$post_id]);
        }

        if (!$post_obj) return '';

        // Use slug if available, otherwise fall back to ?p=ID format
        if (!empty($post_obj->slug)) {
            return home_url($post_obj->slug);
        } else {
            return home_url('?p=' . $post_obj->id);
        }
    }
}

if (!function_exists('the_permalink')) {
    function the_permalink($post_id = 0) {
        echo apply_filters('the_permalink', get_permalink($post_id));
    }
}
