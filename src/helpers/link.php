<?php

if (!function_exists('home_url')) {
    function home_url($path = '') {
        $request = app(\Witals\Framework\Http\Request::class);
        $host = $request->header('Host');
        
        if ($host) {
            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
            $uri = $request->uri();
            if (str_starts_with($uri, 'http')) {
                $protocol = parse_url($uri, PHP_URL_SCHEME);
            }
            $baseUrl = "$protocol://$host";
        } else {
            $baseUrl = env('APP_URL', 'http://localhost');
        }
        
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('get_permalink')) {
    function get_permalink($post_id = 0) {
        $post_obj = null;
        
        // Optimization: If a Post object or proxy is passed, use it directly
        if (is_object($post_id)) {
            $post_obj = $post_id;
        } elseif (!$post_id) {
            $post_obj = $GLOBALS['__presto_current_post'] ?? null;
        } else {
            $orm = app(\Cycle\ORM\ORMInterface::class);
            $repo = $orm->getRepository(\App\Models\Post::class);
            $post_id_val = is_numeric($post_id) ? (int)$post_id : $post_id;
            $post_obj = $repo->findOne(['id' => $post_id_val]);
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

if (!function_exists('admin_url')) {
    function admin_url($path = '', $scheme = 'admin') {
        return home_url('/wp-admin/' . ltrim($path, '/'));
    }
}

if (!function_exists('get_edit_post_link')) {
    function get_edit_post_link($id = 0, $context = 'display') {
        if (!$id && isset($GLOBALS['post'])) {
             $id = $GLOBALS['post']->ID;
        }
        return admin_url("post.php?post=$id&action=edit");
    }
}
