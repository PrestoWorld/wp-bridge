<?php

declare(strict_types=1);

namespace Prestoworld\Bridge\WordPress\Sandbox\Services;

use Cycle\ORM\EntityManagerInterface;
use Prestoworld\Bridge\WordPress\Sandbox\Models\TransformerRegistry;

/**
 * TransformerRegistryService
 * 
 * Manages transformer registry with database backend.
 * Syncs with wporg-marketplace API for automatic updates.
 */
class TransformerRegistryService
{
    public function __construct(
        protected EntityManagerInterface $entityManager
    ) {}

    /**
     * Get transformers for a specific plugin
     */
    public function getForPlugin(string $slug, string $version): array
    {
        $repo = $this->entityManager->getRepository(TransformerRegistry::class);
        
        // Query all transformers for this plugin
        $results = $repo->select()
            ->where('plugin_slug', $slug)
            ->where('enabled', true)
            ->fetchAll();
        
        $transformers = [];
        foreach ($results as $record) {
            // Check version constraint
            if ($this->matchesVersion($version, $record->version_constraint)) {
                $transformers[] = [
                    'id' => $record->transformer_id,
                    'class' => $record->transformer_class,
                    'keywords' => $record->keywords ?? [],
                    'priority' => $record->priority ?? 100,
                    'enabled' => true,
                    'plugin' => $slug
                ];
            }
        }
        
        return $transformers;
    }

    /**
     * Sync transformers from wporg-marketplace API
     */
    public function syncFromMarketplace(string $apiUrl = 'https://api.prestoworld.com/marketplace/transformers'): int
    {
        $synced = 0;
        
        try {
            // Fetch from API
            $response = file_get_contents($apiUrl);
            $data = json_decode($response, true);
            
            if (!$data || !isset($data['transformers'])) {
                return 0;
            }
            
            foreach ($data['transformers'] as $item) {
                $this->upsertTransformer($item);
                $synced++;
            }
            
        } catch (\Throwable $e) {
            // Log error but don't fail
            error_log("Failed to sync transformers: " . $e->getMessage());
        }
        
        return $synced;
    }

    /**
     * Upsert transformer record
     */
    protected function upsertTransformer(array $data): void
    {
        $repo = $this->entityManager->getRepository(TransformerRegistry::class);
        
        // Find existing
        $existing = $repo->select()
            ->where('plugin_slug', $data['plugin_slug'])
            ->where('transformer_id', $data['transformer_id'])
            ->fetchOne();
        
        if ($existing) {
            // Update
            $existing->transformer_class = $data['transformer_class'];
            $existing->keywords = $data['keywords'] ?? [];
            $existing->version_constraint = $data['version_constraint'] ?? '*';
            $existing->priority = $data['priority'] ?? 100;
            $existing->metadata = $data['metadata'] ?? [];
            $existing->synced_at = new \DateTime();
            $existing->updated_at = new \DateTime();
        } else {
            // Insert
            $record = new TransformerRegistry();
            $record->plugin_slug = $data['plugin_slug'];
            $record->transformer_id = $data['transformer_id'];
            $record->transformer_class = $data['transformer_class'];
            $record->keywords = $data['keywords'] ?? [];
            $record->version_constraint = $data['version_constraint'] ?? '*';
            $record->priority = $data['priority'] ?? 100;
            $record->metadata = $data['metadata'] ?? [];
            $record->source = 'marketplace';
            $record->synced_at = new \DateTime();
            $record->created_at = new \DateTime();
            $record->updated_at = new \DateTime();
            
            $this->entityManager->persist($record);
        }
        
        $this->entityManager->run();
    }

    /**
     * Register built-in transformers
     */
    public function seedBuiltIn(): void
    {
        $builtIn = [
            [
                'plugin_slug' => 'woocommerce',
                'transformer_id' => 'wc_orders',
                'transformer_class' => \Prestoworld\Bridge\WordPress\Sandbox\Transformers\WooCommerceOrderTransformer::class,
                'keywords' => ['update_post_meta', 'wc_get_orders', 'WC_Order'],
                'version_constraint' => '>=3.0',
                'priority' => 80,
                'source' => 'builtin'
            ],
            // Add more built-in transformers here
        ];
        
        foreach ($builtIn as $data) {
            $this->upsertTransformer($data);
        }
    }

    protected function matchesVersion(string $version, string $constraint): bool
    {
        if ($constraint === '*') return true;
        
        if (str_starts_with($constraint, '>=')) {
            return version_compare($version, substr($constraint, 2), '>=');
        }
        
        if (str_starts_with($constraint, '<=')) {
            return version_compare($version, substr($constraint, 2), '<=');
        }
        
        return version_compare($version, $constraint, '=');
    }
}
