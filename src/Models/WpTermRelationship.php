<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Models;

use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;

#[Entity(table: 'wp_term_relationships')]
class WpTermRelationship
{
    #[Column(type: 'integer', name: 'object_id', primary: true)]
    public int $objectId;

    #[Column(type: 'integer', name: 'term_taxonomy_id', primary: true)]
    public int $termTaxonomyId;

    #[Column(type: 'integer', name: 'term_order', default: 0)]
    public int $termOrder = 0;
}
