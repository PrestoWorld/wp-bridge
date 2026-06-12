<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Admin;

use PrestoWorld\Contracts\Admin\SkinInterface;

abstract class WP_List_Table
{
    protected array $items = [];
    protected array $columns = [];
    protected array $pagination = [];
    protected ?SkinInterface $skin = null;

    public function __construct(?SkinInterface $skin = null)
    {
        $this->skin = $skin;
    }

    public function set_skin(SkinInterface $skin): void
    {
        $this->skin = $skin;
    }

    abstract public function prepare_items(): void;

    public function get_columns(): array
    {
        return [];
    }

    public function display(): void
    {
        if (!$this->skin) {
            echo "<!-- No Admin Skin found -->";
            return;
        }

        echo $this->skin->renderComponent('table', [
            'columns' => $this->get_columns(),
            'items' => $this->items,
            'pagination' => $this->pagination,
        ]);
    }
}
