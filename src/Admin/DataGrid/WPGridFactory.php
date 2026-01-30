<?php

namespace PrestoWorld\Bridge\WordPress\Admin\DataGrid;

use Spiral\DataGrid\Compiler;
use Spiral\DataGrid\Grid;
use Spiral\DataGrid\GridFactory;
use Spiral\DataGrid\GridInterface;
use Spiral\DataGrid\InputInterface;
use Spiral\DataGrid\Specification\Filter;
use Spiral\DataGrid\Specification\Pagination;
use Spiral\DataGrid\Specification\Sorter;
use Spiral\DataGrid\Specification\Value\StringValue;

class WPGridFactory
{
    protected GridFactory $factory;

    public function __construct(GridFactory $factory)
    {
        $this->factory = $factory;
    }

    public static function create(): self
    {
        $compiler = new Compiler();
        
        // Register custom ArrayWriter for handling array sources
        $compiler->addWriter(new ArrayWriter());
        
        return new self(new GridFactory($compiler));
    }

    public function createGrid(iterable $source, array $columns, array $sortable = [], int $perPage = 20, array $inputData = []): GridInterface
    {
        // 1. Create Input from passed data
        $input = new WordPressInput($inputData);
        
        // 2. Clone the factory with the new input source
        $factory = $this->factory->withInput($input);

        // 3. Define standard WP Specifications via GridSchema
        $schema = new \Spiral\DataGrid\GridSchema();

        // Sorting mapping
        foreach ($sortable as $column => $options) {
             // WP defines sortable as ['slug' => ['orderby', true]] or ['slug' => 'orderby']
             $fieldName = is_array($options) ? $options[0] : $options;
             if (is_numeric($column)) { // handle flat array ['column1', 'column2']
                 $column = $fieldName;
             }
             $schema->addSorter($column, new Sorter\Sorter($fieldName));
        }

        // Pagination
        $schema->setPaginator(new Pagination\PagePaginator($perPage));

        // 4. Create the grid using the schema
        return $factory->create($source, $schema);
    }
    
    public function getFactory(): GridFactory
    {
        return $this->factory;
    }
}
