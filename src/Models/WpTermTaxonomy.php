<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Models;

use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity(table: 'wp_term_taxonomy')]
class WpTermTaxonomy
{
    #[Column(type: 'primary', name: 'term_taxonomy_id')]
    public int $id;

    #[Column(type: 'integer', name: 'term_id')]
    public int $termId;

    #[Column(type: 'string', name: 'taxonomy')]
    public string $taxonomy = '';

    #[Column(type: 'text', name: 'description', nullable: true)]
    public ?string $description = null;

    #[Column(type: 'integer', name: 'parent', default: 0)]
    public int $parent = 0;

    #[Column(type: 'integer', name: 'count', default: 0)]
    public int $count = 0;

    #[BelongsTo(target: WpTerm::class, innerKey: 'term_id')]
    public ?WpTerm $term = null;
}
