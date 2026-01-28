<?php

namespace Prestoworld\Bridge\WordPress\Admin\DataGrid;

use Spiral\DataGrid\Compiler;
use Spiral\DataGrid\SpecificationInterface;
use Spiral\DataGrid\WriterInterface;
use Spiral\DataGrid\Specification\Filter\Like;
use Spiral\DataGrid\Specification\Pagination\Limit;
use Spiral\DataGrid\Specification\Sorter\AscSorter;
use Spiral\DataGrid\Specification\Sorter\DescSorter;

/**
 * Array Writer for Spiral DataGrid
 * Handles sorting, filtering, and pagination on PHP arrays
 */
class ArrayWriter implements WriterInterface
{
    public function write(mixed $source, SpecificationInterface $specification, Compiler $compiler): mixed
    {
        if (!is_array($source)) {
            return null;
        }

        // Handle Sorting (AscSorter/DescSorter are the actual implementations)
        if ($specification instanceof AscSorter || $specification instanceof DescSorter) {
            return $this->applySorter($source, $specification);
        }

        // Handle Pagination
        if ($specification instanceof Limit) {
            return $this->applyLimit($source, $specification);
        }

        // Handle Filters (if needed)
        if ($specification instanceof Like) {
            return $this->applyLike($source, $specification);
        }

        return null;
    }

    protected function applySorter(array $source, AscSorter|DescSorter $sorter): array
    {
        try {
            // Use reflection to access private expressions field
            $reflection = new \ReflectionClass($sorter);
            $property = $reflection->getProperty('expressions');
            $property->setAccessible(true);
            $expressions = $property->getValue($sorter);
            
            // Get first field (most common case)
            $field = $expressions[0] ?? null;
            if (!$field) {
                return $source;
            }

            $direction = $sorter instanceof AscSorter ? 'asc' : 'desc';

            usort($source, function($a, $b) use ($field, $direction) {
                $aVal = is_array($a) ? ($a[$field] ?? '') : ($a->$field ?? '');
                $bVal = is_array($b) ? ($b[$field] ?? '') : ($b->$field ?? '');

                if (is_numeric($aVal) && is_numeric($bVal)) {
                    $result = $aVal <=> $bVal;
                } else {
                    $result = strcmp((string)$aVal, (string)$bVal);
                }

                return $direction === 'desc' ? -$result : $result;
            });

            return $source;
        } catch (\Throwable $e) {
            // If reflection fails, return source unchanged
            error_log("ArrayWriter::applySorter failed: " . $e->getMessage());
            return $source;
        }
    }

    protected function applyLimit(array $source, Limit $limit): array
    {
        $value = $limit->getValue();
        $offset = $value['offset'] ?? 0;
        $limitCount = $value['limit'] ?? count($source);

        return array_slice($source, $offset, $limitCount);
    }

    protected function applyLike(array $source, Like $like): array
    {
        // Use reflection to get field name
        $reflection = new \ReflectionClass($like);
        $property = $reflection->getProperty('field');
        $property->setAccessible(true);
        $field = $property->getValue($like);
        
        $value = $like->getValue();

        return array_filter($source, function($item) use ($field, $value) {
            $itemValue = is_array($item) ? ($item[$field] ?? '') : ($item->$field ?? '');
            return stripos((string)$itemValue, (string)$value) !== false;
        });
    }
}

