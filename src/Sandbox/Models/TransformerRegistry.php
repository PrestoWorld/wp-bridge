<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Sandbox\Models;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

/**
 * TransformerRegistry Model
 * 
 * Stores transformer metadata for WordPress plugins.
 * Can be synced from wporg-marketplace API.
 */
#[Entity(table: 'transformer_registry')]
class TransformerRegistry
{
    #[Column(type: 'primary')]
    public int $id;

    #[Column(type: 'string(100)', nullable: false)]
    public string $plugin_slug;

    #[Column(type: 'string(50)', nullable: false)]
    public string $version_constraint;

    #[Column(type: 'string(100)', nullable: false)]
    public string $transformer_id;

    #[Column(type: 'string(255)', nullable: false)]
    public string $transformer_class;

    #[Column(type: 'json', nullable: true)]
    public ?array $keywords = [];

    #[Column(type: 'json', nullable: true)]
    public ?array $metadata = [];

    #[Column(type: 'boolean', default: true)]
    public bool $enabled = true;

    #[Column(type: 'integer', nullable: true)]
    public ?int $priority = 100;

    #[Column(type: 'string(50)', nullable: true)]
    public ?string $source = 'builtin'; // builtin, marketplace, user

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeInterface $synced_at = null;

    #[Column(type: 'datetime')]
    public \DateTimeInterface $created_at;

    #[Column(type: 'datetime')]
    public \DateTimeInterface $updated_at;
}
