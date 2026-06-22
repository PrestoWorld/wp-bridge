<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Models;

use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;

#[Entity(table: 'wp_terms')]
class WpTerm
{
    #[Column(type: 'primary', name: 'term_id')]
    public int $id;

    #[Column(type: 'string', name: 'name')]
    public string $name = '';

    #[Column(type: 'string', name: 'slug')]
    public string $slug = '';

    #[Column(type: 'integer', name: 'term_group', default: 0)]
    public int $group = 0;
}
