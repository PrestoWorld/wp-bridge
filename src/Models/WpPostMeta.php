<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Models;

use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;

#[Entity(table: 'wp_postmeta')]
class WpPostMeta
{
    #[Column(type: 'primary', name: 'meta_id')]
    public int $id;

    #[Column(type: 'integer', name: 'post_id')]
    public int $postId;

    #[Column(type: 'string', name: 'meta_key', nullable: true)]
    public ?string $metaKey = null;

    #[Column(type: 'text', name: 'meta_value', nullable: true)]
    public ?string $metaValue = null;
}
