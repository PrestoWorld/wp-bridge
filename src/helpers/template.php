<?php

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
        return isset($GLOBALS['__presto_current_post']) && !empty($GLOBALS['__presto_current_post']);
    }
}

if (!function_exists('the_post')) {
    function the_post() {
        do_action('the_post');
    }
}

if (!function_exists('the_title')) {
    function the_title($before = '', $after = '', $display = true) {
        static $recursionCount = 0;
        if ($recursionCount > 2) return '[Recursion Limit]';
        $recursionCount++;

        $post = $GLOBALS['__presto_current_post'] ?? null;
        if (!$post) {
            $recursionCount--;
            return '';
        }
        
        $title = apply_filters('the_title', $post->title ?? '', $post->id ?? 0);
        
        $recursionCount--;
        
        if ($display) {
            echo $before . $title . $after;
        } else {
            return $before . $title . $after;
        }
    }
}

if (!function_exists('the_content')) {
    function the_content($more_link_text = null, $strip_teaser = false) {
        // ... (kept logic from view)
        static $recursionCount = 0;
        if ($recursionCount > 2) return;
        $recursionCount++;

        $post = $GLOBALS['__presto_current_post'] ?? null;
        if (!$post) {
            $recursionCount--;
            return;
        }
        
        $content = apply_filters('the_content', $post->content ?? '');
        
        $recursionCount--;
        echo $content;
    }
}

if (!function_exists('get_the_author_meta')) {
    function get_the_author_meta($field = '', $user_id = false) {
        return 'Admin'; // Mock
    }
}



if (!function_exists('get_template_directory')) {
    function get_template_directory() {
        $theme = app(\PrestoWorld\Theme\ThemeManager::class)->getActiveTheme();
        return $theme ? $theme->getPath() : '';
    }
}

if (!function_exists('get_stylesheet_directory')) {
    function get_stylesheet_directory() {
        return get_template_directory();
    }
}

if (!function_exists('get_template_directory_uri')) {
    function get_template_directory_uri() {
        $theme = app(\PrestoWorld\Theme\ThemeManager::class)->getActiveTheme();
        if (!$theme) return '';
        
        $path = $theme->getPath();
        $publicPath = app()->basePath('public');
        
        if (str_starts_with($path, $publicPath)) {
            return str_replace($publicPath, '', $path);
        }
        
        return '/wp-content/themes/' . $theme->getName();
    }
}

if (!function_exists('get_stylesheet_directory_uri')) {
    function get_stylesheet_directory_uri() {
        return get_template_directory_uri();
    }
}
