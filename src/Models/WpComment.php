<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Models;

use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;

#[Entity(table: 'wp_comments')]
class WpComment
{
    #[Column(type: 'primary', name: 'comment_ID')]
    public int $id;

    #[Column(type: 'integer', name: 'comment_post_ID')]
    public int $postId;

    #[Column(type: 'string', name: 'comment_author')]
    public string $author = '';

    #[Column(type: 'string', name: 'comment_author_email', nullable: true)]
    public ?string $authorEmail = null;

    #[Column(type: 'text', name: 'comment_content')]
    public string $content = '';

    #[Column(type: 'string', name: 'comment_approved', default: '1')]
    public string $approved = '1';

    #[Column(type: 'datetime', name: 'comment_date')]
    public \DateTimeImmutable $date;

    #[Column(type: 'string', name: 'comment_type', default: 'comment')]
    public string $type = 'comment';
}
