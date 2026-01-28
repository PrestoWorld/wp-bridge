<?php

namespace Prestoworld\Bridge\WordPress\Admin\DataGrid;

use Spiral\DataGrid\InputInterface;

class WordPressInput implements InputInterface
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function withNamespace(string $namespace): InputInterface
    {
        return $this;
    }

    public function has(string $option): bool
    {
        return $this->getValue($option) !== null;
    }

    public function hasValue(string $option, mixed $value = null): bool
    {
        $current = $this->getValue($option);

        if ($current === null) {
            return false;
        }

        if ($value !== null) {
            return $current == $value;
        }

        return true;
    }

    public function getValue(string $option, mixed $default = null): mixed
    {
        // 1. Mapping Sort (WP uses 'orderby' and 'order')
        if ($option === 'sort' && isset($this->data['orderby'])) {
            $direction = $this->data['order'] ?? 'asc';
            return [$this->data['orderby'] => $direction];
        }

        // 2. Mapping Page (WP uses 'paged')
        if ($option === 'page' && isset($this->data['paged'])) {
            return (int) $this->data['paged'];
        }

        // 3. Mapping Search (WP uses 's')
        if ($option === 'search' && isset($this->data['s'])) {
            return $this->data['s'];
        }

        // 4. Direct mapping for other filters
        if (array_key_exists($option, $this->data)) {
            return $this->data[$option];
        }

        return $default;
    }
}
