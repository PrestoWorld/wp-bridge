<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Sandbox;

use PrestoWorld\Bridge\WordPress\Sandbox\Transformers\TransformerInterface;

/**
 * Class TransformerEngine
 * 
 * An optimized engine to execute transformers at scale (1M+ transformers).
 * Instead of iterating through every transformer, it uses an "AST Fingerprint" or "Pattern Index"
 * to only select transformers relevant to the specific code snippet being compiled.
 */
class TransformerEngine
{
    protected TransformerRepository $repository;
    protected ?Storage\CompiledStorageInterface $storage = null;
    
    // Pattern Index
    protected array $patternIndex = [];

    public function __construct(
        TransformerRepository $repository,
        Storage\CompiledStorageInterface $storage = null
    ) {
        $this->repository = $repository;
        $this->storage = $storage;
    }

    public function getStorage(): ?Storage\CompiledStorageInterface
    {
        return $this->storage;
    }

    /**
     * Smart Compile: Analyzes source code first, then fetches ONLY relevant transformers.
     */
    public function compile(string $source, string $fileKey = ''): string
    {
        // 0. Caching Layer (Distributed & Local)
        if ($fileKey && $this->storage) {
            // If exists in storage, just return it (Hydration happens when executed via getPath, 
            // but if we just need string content, get() is enough)
            if ($cached = $this->storage->get($fileKey)) {
                return $cached;
            }
        }
        
        // 1. Analyze Source (Tokenization)
        // ... (existing logic) ...
        // ... (existing logic) ...
        // Find keywords present in the source code
        // This is O(N) where N is source length. Fast.
        $tokens = token_get_all($source);
        $relevantTransformerIds = [];

        foreach ($tokens as $token) {
            if (is_array($token)) {
                // $token[0] is Type, $token[1] is Content
                $content = $token[1];
                
                // If this keyword/function exists in our Repo Index, mark those transformers as needed
                // In reality, this lookup happens against a Redis/BloomFilter for speed.
                if (isset($this->patternIndex[$content])) {
                    foreach ($this->patternIndex[$content] as $tId) {
                        $relevantTransformerIds[$tId] = true; // Use key for dedup
                    }
                }
            }
        }

        // 2. Load Only Relevant Transformers
        if (empty($relevantTransformerIds)) {
            if ($fileKey && $this->storage) {
                $this->storage->put($fileKey, $source);
            }
            return $source;
        }

        $transformers = [];
        $context = new Context('unknown'); // In real compiled, we pass this in

        foreach (array_keys($relevantTransformerIds) as $id) {
            $t = $this->repository->load($id);
            if (!$t) continue;
            
            // Check Requirements (if any)
            if (method_exists($t, 'getRequirements')) {
                foreach ($t->getRequirements() as $req) {
                    if (!$req->check($context)) {
                        continue 2; // Skip this transformer
                    }
                }
            }
            
            $transformers[] = $t;
        }

        // 3. Sort & Apply
        usort($transformers, fn($a, $b) => $b->getPriority() <=> $a->getPriority());

        foreach ($transformers as $transformer) {
            $source = $transformer->transform($source);
        }

        // Cache the result !
        if ($fileKey && $this->storage) {
            $this->storage->put($fileKey, $source);
        }

        return $source;
    }

    public function indexTransformer(string $id, array $keywords): void
    {
        // Add to index
        foreach ($keywords as $keyword) {
            $this->patternIndex[$keyword][] = $id;
        }
    }
}
