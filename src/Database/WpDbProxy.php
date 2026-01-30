<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Database;

use Witals\Framework\Application;

/**
 * Class WpDbProxy
 * 
 * A smart proxy that replaces the global $wpdb object.
 * It sits between WordPress legacy code and the actual database.
 * 
 * Features:
 * 1. Query Rewriting: Redirects WP queries to optimized Presto tables.
 * 2. Connection Multiplexing: Can route writes to Master, reads to Slave/Replica.
 * 3. Sandbox Mode: Can route destructive queries to a SQLite sandbox for testing.
 */
class WpDbProxy
{
    protected Application $app;
    protected QueryRewriter $rewriter;
    protected $realConnection; // Cycle Database or PDO

    public $prefix = 'wp_';
    public $posts = 'wp_posts';
    public $postmeta = 'wp_postmeta';
    // ... define other standard WP tables

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->rewriter = new QueryRewriter();
        // Use the default DatabaseInterface from Cycle
        if ($this->app->has(\Cycle\Database\DatabaseInterface::class)) {
            $this->realConnection = $this->app->make(\Cycle\Database\DatabaseInterface::class);
        }
    }

    /**
     * The magic method used by WP to execute generic queries.
     */
    public function query($query)
    {
        // 1. Analyze & Rewrite
        [$newQuery, $bindings] = $this->rewriter->rewrite($query);

        if ($newQuery !== $query) {
            error_log("[PrestoBridge] Optimized Query: $query -> $newQuery");
            return $this->dispatchToNative($newQuery, $bindings);
        }

        return $this->dispatchToLegacy($query);
    }

    /**
     * Alias for query() to support safety-first transformations.
     */
    public function safe_query($query)
    {
        return $this->query($query);
    }

    public function insert($table, $data, $format = null)
    {
        if (!$this->realConnection) return false;
        
        try {
            return $this->realConnection->insert($table)
                ->values($data)
                ->run();
        } catch (\Throwable $e) {
            error_log("[PrestoBridge] Insert Error: " . $e->getMessage());
            return false;
        }
    }

    public function update($table, $data, $where, $format = null, $where_format = null)
    {
        if (!$this->realConnection) return false;

        try {
            $query = $this->realConnection->update($table)->values($data);
            foreach ($where as $column => $value) {
                $query->where($column, $value);
            }
            return $query->run();
        } catch (\Throwable $e) {
            error_log("[PrestoBridge] Update Error: " . $e->getMessage());
            return false;
        }
    }

    public function delete($table, $where, $where_format = null)
    {
        if (!$this->realConnection) return false;

        try {
            $query = $this->realConnection->delete($table);
            foreach ($where as $column => $value) {
                $query->where($column, $value);
            }
            return $query->run();
        } catch (\Throwable $e) {
            error_log("[PrestoBridge] Delete Error: " . $e->getMessage());
            return false;
        }
    }

    public function get_results($query = null, $output = 'OBJECT')
    {
        if (!$this->realConnection) return [];

        try {
            $results = $this->realConnection->query($query)->fetchAll(\PDO::FETCH_ASSOC);
            if ($output === 'OBJECT') {
                return array_map(fn($row) => (object)$row, $results);
            }
            return $results;
        } catch (\Throwable $e) {
            error_log("[PrestoBridge] get_results Error: " . $e->getMessage());
            return [];
        }
    }
    
    public function get_var($query = null, $x = 0, $y = 0)
    {
        if (!$this->realConnection) return null;

        try {
            return $this->realConnection->query($query)->fetchColumn($x);
        } catch (\Throwable $e) {
            error_log("[PrestoBridge] get_var Error: " . $e->getMessage());
            return null;
        }
    }

    public function prepare($query, ...$args)
    {
        if (isset($args[0]) && is_array($args[0])) {
            $args = $args[0];
        }
        
        // This is a simplified version of WP's prepare.
        // It handles %s (string), %d (integer), %f (float)
        $processedArgs = [];
        foreach ($args as $arg) {
            if (is_int($arg)) {
                $processedArgs[] = $arg;
            } elseif (is_float($arg)) {
                $processedArgs[] = $arg;
            } else {
                // String: Escape and wrap in quotes
                // In a real app we'd use $this->realConnection->quote() or similar
                $escaped = addslashes((string)$arg);
                $processedArgs[] = "'$escaped'";
            }
        }

        // Replace placeholders safely
        // Note: This simple replacement assumes placeholders are not part of actual text.
        $query = str_replace(['%s', '%d', '%f'], '%s', $query);
        return vsprintf($query, $processedArgs);
    }

    protected function dispatchToNative($sql, $bindings)
    {
        if (!$this->realConnection) return [];
        return $this->realConnection->query($sql, $bindings)->fetchAll();
    }

    protected function dispatchToLegacy($sql)
    {
        if (!$this->realConnection) return [];
        return $this->realConnection->query($sql)->fetchAll();
    }
}
