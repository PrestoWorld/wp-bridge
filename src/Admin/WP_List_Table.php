<?php

namespace Prestoworld\Bridge\WordPress\Admin;

use Spiral\DataGrid\GridInterface;
use Prestoworld\Bridge\WordPress\Admin\DataGrid\WPGridFactory;

/**
 * Modern implementation of WP_List_Table using Spiral DataGrid.
 * This class provides the same API as the original WordPress class
 * but allows for more robust data handling.
 */
abstract class WP_List_Table
{
    /**
     * The grid instance from Spiral DataGrid
     */
    protected ?GridInterface $grid = null;

    /**
     * The grid factory
     */
    protected ?WPGridFactory $gridFactory = null;

    /**
     * The current screen
     */
    protected $screen;

    /**
     * The list of items
     */
    public array $items = [];

    protected array $_args = [];
    protected array $_pagination_args = [];
    protected array $_column_headers = [];
    protected array $_actions = [];
    protected string $_pagination = '';

    /**
     * Constructor
     */
    public function __construct(array $args = [])
    {
        $this->_args = array_merge([
            'singular' => '',
            'plural'   => '',
            'ajax'     => false,
            'screen'   => null,
        ], $args);

        $this->screen = convert_to_screen($this->_args['screen']);
    }

    /**
     * Set the grid factory
     */
    public function setGridFactory(WPGridFactory $factory): void
    {
        $this->gridFactory = $factory;
    }

    /**
     * Prepares the list of items for displaying.
     * @abstract
     */
    abstract public function prepare_items();

    public function bind_grid(iterable $source = [], int $totalItems = 0, int $perPage = 20, array $requestData = []): void
    {
        // Handle bulk actions
        $this->process_bulk_action();

        $columns = $this->get_columns();
        $hidden = get_hidden_columns($this->screen);
        $sortable = $this->get_sortable_columns();
        $primary = $this->get_default_primary_column_name();

        $this->_column_headers = [$columns, $hidden, $sortable, $primary];

        // TEMPORARY: Force manual handling to isolate error
        $items = is_array($source) ? $source : iterator_to_array($source);
        
        // Handle sorting manually
        if (!empty($_GET['orderby']) && isset($sortable[$_GET['orderby']])) {
            $orderby = $_GET['orderby'];
            $order = $_GET['order'] ?? 'asc';
            
            // Extract field name from sortable config
            $sortableConfig = $sortable[$orderby];
            $fieldName = is_array($sortableConfig) ? $sortableConfig[0] : $sortableConfig;
            
            usort($items, function($a, $b) use ($fieldName, $order) {
                $aVal = is_array($a) ? ($a[$fieldName] ?? '') : ($a->$fieldName ?? '');
                $bVal = is_array($b) ? ($b[$fieldName] ?? '') : ($b->$fieldName ?? '');
                
                if (is_numeric($aVal) && is_numeric($bVal)) {
                    $result = $aVal <=> $bVal;
                } else {
                    $result = strcmp((string)$aVal, (string)$bVal);
                }
                
                return $order === 'desc' ? -$result : $result;
            });
        }
        
        // Handle pagination manually
        $currentPage = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($currentPage - 1) * $perPage;
        $this->items = array_slice($items, $offset, $perPage);
        
        $totalItems = count($items);

        $this->set_pagination_args([
            'total_items' => $totalItems,
            'per_page'    => $perPage,
            'total_pages' => ceil($totalItems / $perPage),
        ]);
    }

    public function get_column_info()
    {
        if (isset($this->_column_headers)) {
            $columns = $this->_column_headers[0];
            $hidden  = $this->_column_headers[1];
            $sortable = $this->_column_headers[2];
            $primary = $this->_column_headers[3];

            return [$columns, $hidden, $sortable, $primary];
        }

        // Default fallback if bind_grid wasn't called (manual prepare_items)
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();
        $primary = $this->get_default_primary_column_name();
        $this->_column_headers = [$columns, $hidden, $sortable, $primary];

        return [$columns, $hidden, $sortable, $primary];
    }

    public function get_columns()
    {
        return [];
    }

    protected function get_sortable_columns()
    {
        return [];
    }

    protected function get_bulk_actions()
    {
        return [];
    }

    protected function get_views()
    {
        return [];
    }
    
    public function get_table_classes()
    {
        return ['widefat', 'fixed', 'striped', $this->_args['plural']];
    }

    public function get_default_primary_column_name()
    {
        $columns = $this->get_columns();
        foreach ($columns as $column => $title) {
            if ('cb' === $column) {
                continue;
            }
            return $column;
        }
        return '';
    }
    
    public function get_primary_column_name()
    {
        if (isset($this->_column_headers[3])) {
            return $this->_column_headers[3];
        }
        return $this->get_default_primary_column_name();
    }

    public function current_action()
    {
        if (isset($_REQUEST['filter_action']) && !empty($_REQUEST['filter_action'])) {
            return false;
        }

        if (isset($_REQUEST['action']) && -1 != $_REQUEST['action'] && !empty($_REQUEST['action'])) {
            return $_REQUEST['action'];
        }

        if (isset($_REQUEST['action2']) && -1 != $_REQUEST['action2'] && !empty($_REQUEST['action2'])) {
            return $_REQUEST['action2'];
        }

        return false;
    }

    public function process_bulk_action(): void
    {
        $action = $this->current_action();
        if (!$action) {
            return;
        }

        $items = $_REQUEST['bulk_edit'] ?? [];
        if (empty($items)) {
            return;
        }

        $method = 'handle_bulk_' . $action;
        if (method_exists($this, $method)) {
            $this->$method($items);
        }
    }

    public function set_pagination_args(array $args): void
    {
        $this->_pagination_args = $args;
    }

    public function get_pagination_arg(string $key)
    {
        return $this->_pagination_args[$key] ?? null;
    }

    public function has_items(): bool
    {
        return !empty($this->items);
    }

    public function search_box($text, $input_id)
    {
        if (empty($_REQUEST['s']) && !$this->has_items()) {
            return;
        }

        $input_id = $input_id . '-search-input';

        if (!empty($_REQUEST['orderby'])) {
            echo '<input type="hidden" name="orderby" value="' . esc_attr($_REQUEST['orderby']) . '" />';
        }
        if (!empty($_REQUEST['order'])) {
            echo '<input type="hidden" name="order" value="' . esc_attr($_REQUEST['order']) . '" />';
        }
        ?>
        <p class="search-box">
            <label class="screen-reader-text" for="<?php echo esc_attr($input_id); ?>"><?php echo $text; ?>:</label>
            <input type="search" id="<?php echo esc_attr($input_id); ?>" name="s" value="<?php _admin_search_query(); ?>" />
            <?php submit_button($text, '', '', false, array('id' => 'search-submit')); ?>
        </p>
        <?php
    }

    public function display(): void
    {
        $this->views();
        $this->display_tablenav('top');
        ?>
        <table class="wp-list-table <?php echo implode(' ', $this->get_table_classes()); ?>">
            <thead>
                <tr><?php $this->print_column_headers(); ?></tr>
            </thead>
            <tbody id="the-list">
                <?php $this->display_rows_or_placeholder(); ?>
            </tbody>
            <tfoot>
                <tr><?php $this->print_column_headers(false); ?></tr>
            </tfoot>
        </table>
        <?php
        $this->display_tablenav('bottom');
    }

    protected function display_rows_or_placeholder(): void
    {
        if ($this->has_items()) {
            $this->display_rows();
        } else {
            echo '<tr class="no-items"><td class="colspanchange" colspan="' . count($this->get_columns()) . '">No items found.</td></tr>';
        }
    }

    public function display_rows(): void
    {
        foreach ($this->items as $item) {
            $this->single_row($item);
        }
    }

    public function single_row($item): void
    {
        echo '<tr>';
        $this->single_row_columns($item);
        echo '</tr>';
    }

    protected function single_row_columns($item): void
    {
        [$columns, $hidden, $sortable, $primary] = $this->get_column_info();

        foreach ($columns as $column_name => $column_display_name) {
            $classes = "$column_name column-$column_name";
            if ($primary === $column_name) {
                $classes .= ' has-row-actions column-primary';
            }
            if (in_array($column_name, $hidden)) {
                $classes .= ' hidden';
            }

            $data = 'data-colname="' . esc_attr($column_display_name) . '"';
            $attributes = "class='$classes' $data";

            echo "<td $attributes>";
            if (method_exists($this, 'column_' . $column_name)) {
                echo call_user_func([$this, 'column_' . $column_name], $item);
            } else {
                echo $this->column_default($item, $column_name);
            }
            // Handle row actions if primary
            if ($primary === $column_name) {
                echo $this->handle_row_actions($item, $column_name, $primary);
            }
            echo "</td>";
        }
    }

    public function column_default($item, $column_name)
    {
        $value = '';
        if (is_array($item)) {
            $value = $item[$column_name] ?? '';
        } else {
            $value = $item->$column_name ?? '';
        }
        
        // Safely convert to string
        if (is_array($value)) {
            return json_encode($value);
        }
        
        return (string)$value;
    }

    public function column_cb($item)
    {
         $id = is_array($item) ? ($item['ID'] ?? $item['id'] ?? 0) : ($item->ID ?? $item->id ?? 0);
         return sprintf('<input type="checkbox" name="bulk_edit[]" value="%s" />', $id);
    }

    protected function handle_row_actions($item, $column_name, $primary)
    {
        return $primary === $column_name ? '<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>' : '';
    }

    protected function row_actions(array $actions, bool $always_visible = false): string
    {
        $action_count = count($actions);
        $i = 0;

        if (!$action_count) {
            return '';
        }

        $out = '<div class="' . ($always_visible ? 'row-actions visible' : 'row-actions') . '">';
        foreach ($actions as $action => $link) {
            ++$i;
            ( $i == $action_count ) ? $sep = '' : $sep = ' | ';
            $out .= "<span class='$action'>$link$sep</span>";
        }
        $out .= '</div>';

        return $out;
    }

    protected function print_column_headers(bool $with_id = true): void
    {
        [$columns, $hidden, $sortable, $primary] = $this->get_column_info();

        foreach ($columns as $column_key => $column_display_name) {
            $class = ["manage-column", "column-$column_key"];
            if (in_array($column_key, $hidden)) {
                $class[] = 'hidden';
            }
            if ($column_key === $primary) {
                $class[] = 'column-primary';
            }

            if (isset($sortable[$column_key])) {
                $class[] = 'sortable';
                // For logic, we assume we can build URL from existing args + order/orderby
                // But in a real implementation we need remove_query_arg or add_query_arg safely
                $orderby = $_GET['orderby'] ?? '';
                $order = $_GET['order'] ?? 'asc';

                if ($orderby === $column_key) {
                    $class[] = $order;
                    $new_order = ($order === 'asc') ? 'desc' : 'asc';
                } else {
                    $class[] = 'asc';
                    $new_order = 'asc';
                }

                $current_url = $_SERVER['REQUEST_URI'] ?? '';
                $url = add_query_arg(['orderby' => $column_key, 'order' => $new_order], $current_url);
                echo "<th scope='col' class='" . implode(' ', $class) . "'><a href='$url'><span>$column_display_name</span><span class='sorting-indicator'></span></a></th>";
            } else {
                echo "<th scope='col' class='" . implode(' ', $class) . "'>$column_display_name</th>";
            }
        }
    }

    protected function display_tablenav(string $which): void
    {
        ?>
        <div class="tablenav <?php echo esc_attr($which); ?>">
            <div class="alignleft actions bulkactions">
                <?php $this->bulk_actions($which); ?>
            </div>
            <?php 
            $this->extra_tablenav($which);
            $this->pagination($which); 
            ?>
            <br class="clear" />
        </div>
        <?php
    }

    protected function extra_tablenav($which) 
    {
        // For plugins to hook into
    }

    protected function bulk_actions(string $which = ''): void
    {
        $actions = $this->get_bulk_actions();
        if (empty($actions)) return;

        $name = $which === 'top' ? 'action' : 'action2';
        $input_id = "bulk-action-selector-" . $which;
        
        // Prepare Data for SolidJS
        $data = [
            'name' => $name,
            'id' => $input_id,
            'actions' => []
        ];
        foreach ($actions as $k => $v) {
            $data['actions'][] = ['value' => $k, 'label' => $v];
        }
        $json = htmlspecialchars(json_encode($data), ENT_QUOTES, 'UTF-8');

        // Mount Point for SolidJS Core
        echo "<div data-solid-component='BulkActions' data-config='{$json}'></div>";

        // Fallback
        echo '<noscript>';
        echo '<label for="' . esc_attr($input_id) . '" class="screen-reader-text">Select bulk action</label>';
        echo '<select name="' . $name . '" id="' . esc_attr($input_id) . '">';
        echo '<option value="-1">Bulk actions</option>';
        foreach ($actions as $n => $title) {
            echo '<option value="' . esc_attr($n) . '">' . $title . '</option>';
        }
        echo '</select>';
        submit_button('Apply', 'action', '', false, ['id' => 'doaction']);
        echo '</noscript>';
    }

    protected function pagination(string $which): void
    {
        $total_items = $this->get_pagination_arg('total_items');
        if (!$total_items) return;

        // Simple Pagination Rendering
        echo '<div class="tablenav-pages">';
        echo '<span class="displaying-num">' . sprintf('%s items', number_format_i18n($total_items)) . '</span>';
        echo '</div>';
    }

    public function views(): void
    {
        $views = $this->get_views();
        if (empty($views)) return;

        echo "<ul class='subsubsub'>";
        foreach ($views as $class => $view) {
            echo "<li class='$class'>$view</li>";
        }
        echo "</ul>";
    }
}

if (!function_exists('convert_to_screen')) {
    function convert_to_screen($hook_name) {
        // Primitive shim
        return $hook_name;
    }
}
