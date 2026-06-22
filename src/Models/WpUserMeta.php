<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Models;

use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;

#[Entity(table: 'wp_usermeta')]
class WpUserMeta
{
    #[Column(type: 'primary', name: 'umeta_id')]
    public int $id;

    #[Column(type: 'integer', name: 'user_id')]
    public int $userId;

    #[Column(type: 'string', name: 'meta_key', nullable: true)]
    public ?string $metaKey = null;

    #[Column(type: 'text', name: 'meta_value', nullable: true)]
    public ?string $metaValue = null;
}
