<?php

declare(strict_types=1);

namespace Prestoworld\Bridge\WordPress\Settings;

class SettingsRegistry
{
    /**
     * @var array<string, array>
     * [page_slug => [section_id => [title, callback, section_args]]]
     */
    protected array $sections = [];

    /**
     * @var array<string, array>
     * [page_slug => [section_id => [field_id => [title, callback, page, section, args]]]]
     */
    protected array $fields = [];

    /**
     * @var array<string, array>
     * [option_group => [option_name => args]]
     */
    protected array $registeredSettings = [];

    public function addSection(string $id, string $title, ?callable $callback, string $page): void
    {
        $this->sections[$page][$id] = [
            'id' => $id,
            'title' => $title,
            'callback' => $callback,
        ];
    }

    public function addField(string $id, string $title, callable $callback, string $page, string $section = 'default', array $args = []): void
    {
        $this->fields[$page][$section][$id] = [
            'id' => $id,
            'title' => $title,
            'callback' => $callback,
            'args' => $args
        ];
    }

    public function registerSetting(string $optionGroup, string $optionName, array $args = []): void
    {
        $this->registeredSettings[$optionGroup][$optionName] = $args;
    }

    public function getSections(string $page): array
    {
        return $this->sections[$page] ?? [];
    }

    public function getFields(string $page, string $section): array
    {
        return $this->fields[$page][$section] ?? [];
    }
}
