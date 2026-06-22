<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Models;

use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;

#[Entity(table: 'wp_options')]
class WpOption
{
    #[Column(type: 'primary', name: 'option_id')]
    public int $id;

    #[Column(type: 'string', name: 'option_name')]
    public string $optionName;

    #[Column(type: 'text', name: 'option_value', nullable: true)]
    public ?string $optionValue = null;

    #[Column(type: 'string', name: 'autoload', default: 'yes')]
    public string $autoload = 'yes';
}
