<?php

if (!function_exists('is_page')) {
    function is_page($page = '') {
        $post = $GLOBALS['__presto_current_post'] ?? null;
        if (!$post || $post->type !== 'page') return false;
        if (empty($page)) return true;
        if (is_object($page)) {
            return (int)$post->id === (int)($page->id ?? 0);
        }
        return ($post->slug === $page || $post->id == $page || $post->title === $page);
    }
}

if (!function_exists('is_single')) {
    function is_single($post_val = '') {
        $post = $GLOBALS['__presto_current_post'] ?? null;
        if (!$post || $post->type !== 'post') return false;
        if (empty($post_val)) return true;
        if (is_object($post_val)) {
            return (int)$post->id === (int)($post_val->id ?? 0);
        }
        return ($post->slug === $post_val || $post->id == $post_val || $post->title === $post_val);
    }
}

if (!function_exists('get_queried_object')) {
    function get_queried_object() {
        return $GLOBALS['__presto_current_post'] ?? null;
    }
}

if (!function_exists('get_the_ID')) {
    function get_the_ID() {
        $post = $GLOBALS['__presto_current_post'] ?? null;
        return $post->id ?? 0;
    }
}
