<?php

namespace HfH\Catalog\Admin\Table;

use HfH\Catalog\Admin\Page\CatalogPage;
use HfH\Catalog\Admin\Page\FilterPage;
use HfH\Catalog\Plugin;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class FiltersListTable extends \WP_List_Table
{
    private array $filters;
    private array $options_by_filter;

    public function __construct(array $filters, array $options_by_filter)
    {
        parent::__construct([
            'singular' => 'filter',
            'plural'   => 'filters',
            'ajax'     => false,
        ]);
        $this->filters = $filters;
        $this->options_by_filter = $options_by_filter;
    }

    public function get_columns(): array
    {
        return [
            'name'    => __('Filter', Plugin::TEXT_DOMAIN),
            'options' => __('Options', Plugin::TEXT_DOMAIN),
        ];
    }

    public function prepare_items(): void
    {
        $this->items = $this->filters;
        $this->_column_headers = [$this->get_columns(), [], []];
    }

    protected function column_name($filter): string
    {
        $filter_url = FilterPage::url(['id' => $filter->id]);
        $index = array_search($filter, $this->filters, true);

        $actions = [
            'edit' => sprintf(
                '<a href="%s" aria-label="%s">%s</a>',
                esc_url($filter_url),
                /* translators: %s: filter name */
                esc_attr(sprintf(__('Edit “%s”', Plugin::TEXT_DOMAIN), $filter->name)),
                esc_html__('Edit', Plugin::TEXT_DOMAIN)
            ),
        ];

        if ($index > 0) {
            $actions['move_up'] = sprintf(
                '<a href="%s" aria-label="%s">%s</a>',
                esc_url($this->action_url(CatalogPage::ACTION_MOVE_FILTER, $filter, ['direction' => 'up'])),
                /* translators: %s: filter name */
                esc_attr(sprintf(__('Move “%s” up', Plugin::TEXT_DOMAIN), $filter->name)),
                esc_html__('Move up', Plugin::TEXT_DOMAIN)
            );
        }

        if ($index < count($this->filters) - 1) {
            $actions['move_down'] = sprintf(
                '<a href="%s" aria-label="%s">%s</a>',
                esc_url($this->action_url(CatalogPage::ACTION_MOVE_FILTER, $filter, ['direction' => 'down'])),
                /* translators: %s: filter name */
                esc_attr(sprintf(__('Move “%s” down', Plugin::TEXT_DOMAIN), $filter->name)),
                esc_html__('Move down', Plugin::TEXT_DOMAIN)
            );
        }

        $actions['delete'] = sprintf(
            '<a href="%s" class="submitdelete" aria-label="%s" onclick="return confirm(\'%s\');">%s</a>',
            esc_url($this->action_url(CatalogPage::ACTION_DELETE_FILTER, $filter)),
            /* translators: %s: filter name */
            esc_attr(sprintf(__('Delete “%s”', Plugin::TEXT_DOMAIN), $filter->name)),
            /* translators: %s: filter name */
            esc_js(sprintf(__('Delete “%s” and all its options? This cannot be undone.', Plugin::TEXT_DOMAIN), $filter->name)),
            esc_html__('Delete', Plugin::TEXT_DOMAIN)
        );

        return sprintf(
            '<strong><a class="row-title" href="%s">%s</a></strong><br>%s%s',
            esc_url($filter_url),
            esc_html($filter->name),
            esc_html($filter->slug),
            $this->row_actions($actions)
        );
    }

    protected function column_options($filter): string
    {
        $filter_options = $this->options_by_filter[$filter->id] ?? [];
        return $filter_options ? esc_html(implode(', ', wp_list_pluck($filter_options, 'name'))) : '—';
    }

    private function action_url(string $action, object $filter, array $args = []): string
    {
        return CatalogPage::action_url($action, ['id' => $filter->catalog_id, 'filter_id' => $filter->id] + $args);
    }

    protected function display_tablenav($which): void
    {
        if ($which === 'top') {
            return;
        }
        parent::display_tablenav($which);
    }
}
