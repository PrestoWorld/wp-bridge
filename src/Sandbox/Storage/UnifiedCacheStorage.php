<?php

declare(strict_types=1);

namespace Prestoworld\Bridge\WordPress\Sandbox\Storage;

/**
 * Class UnifiedCacheStorage
 * 
 * Uses the same driver mechanism as HookRegistry (SQLite/Redis/MongoDB)
 * to store compiled code. This ensures consistency and reduces complexity.
 */
class UnifiedCacheStorage implements CompiledStorageInterface
{
    protected string $driver;
    protected $connection; // SQLite PDO, Redis, or MongoDB Client
    protected string $localTempDir;

    public function __construct(string $driver, $connection)
    {
        $this->driver = $driver;
        $this->connection = $connection;
        
        // Use /dev/shm for fastest execution if available
        $this->localTempDir = is_dir('/dev/shm') 
            ? '/dev/shm/presto_compiled' 
            : sys_get_temp_dir() . '/presto_compiled';
        
        if (!is_dir($this->localTempDir)) {
            mkdir($this->localTempDir, 0755, true);
        }
    }

    public function has(string $key): bool
    {
        return match($this->driver) {
            'sqlite' => $this->hasInSQLite($key),
            'redis' => $this->connection->exists('compiled:' . $key) > 0,
            'mongodb' => $this->hasInMongo($key),
            default => false
        };
    }

    public function get(string $key): ?string
    {
        return match($this->driver) {
            'sqlite' => $this->getFromSQLite($key),
            'redis' => $this->connection->get('compiled:' . $key) ?: null,
            'mongodb' => $this->getFromMongo($key),
            default => null
        };
    }

    public function put(string $key, string $code): void
    {
        match($this->driver) {
            'sqlite' => $this->putToSQLite($key, $code),
            'redis' => $this->connection->setEx('compiled:' . $key, 86400, $code),
            'mongodb' => $this->putToMongo($key, $code),
            default => null
        };
        
        // Also write to local for immediate execution
        $this->writeLocal($key, $code);
    }

    public function getPath(string $key): string
    {
        // Normalize key to safe filename
        $safeKey = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
        $localPath = $this->localTempDir . '/' . $safeKey . '.php';

        if (file_exists($localPath)) {
            return $localPath;
        }

        $code = $this->get($key);
        if ($code) {
            $this->writeLocal($key, $code);
            return $localPath;
        }

        throw new \RuntimeException("Compiled code not found: $key");
    }

    protected function writeLocal(string $key, string $code): void
    {
        $safeKey = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
        $localPath = $this->localTempDir . '/' . $safeKey . '.php';
        
        $tmpPath = $localPath . '.tmp.' . uniqid();
        file_put_contents($tmpPath, $code);
        rename($tmpPath, $localPath);
        
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($localPath, true);
        }
    }

    // SQLite Implementation
    protected function hasInSQLite(string $key): bool
    {
        $stmt = $this->connection->prepare("SELECT COUNT(*) FROM compiled_cache WHERE cache_key = ?");
        $stmt->execute([$key]);
        return $stmt->fetchColumn() > 0;
    }

    protected function getFromSQLite(string $key): ?string
    {
        $stmt = $this->connection->prepare("SELECT code FROM compiled_cache WHERE cache_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetchColumn();
        return $result ?: null;
    }

    protected function putToSQLite(string $key, string $code): void
    {
        // Ensure table exists
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS compiled_cache (
                cache_key TEXT PRIMARY KEY,
                code TEXT NOT NULL,
                created_at INTEGER DEFAULT (strftime('%s', 'now'))
            )
        ");
        
        $stmt = $this->connection->prepare("INSERT OR REPLACE INTO compiled_cache (cache_key, code) VALUES (?, ?)");
        $stmt->execute([$key, $code]);
    }

    // MongoDB Implementation
    protected function hasInMongo(string $key): bool
    {
        $collection = $this->connection->selectDatabase('presto_core')->selectCollection('compiled_cache');
        return $collection->countDocuments(['_id' => $key]) > 0;
    }

    protected function getFromMongo(string $key): ?string
    {
        $collection = $this->connection->selectDatabase('presto_core')->selectCollection('compiled_cache');
        $doc = $collection->findOne(['_id' => $key]);
        return $doc['code'] ?? null;
    }

    protected function putToMongo(string $key, string $code): void
    {
        $collection = $this->connection->selectDatabase('presto_core')->selectCollection('compiled_cache');
        $collection->updateOne(
            ['_id' => $key],
            ['$set' => ['code' => $code, 'updated_at' => new \MongoDB\BSON\UTCDateTime()]],
            ['upsert' => true]
        );
    }
}
