<?php

namespace HfH\Catalog\Admin\Table;

use HfH\Catalog\Admin\Page\CatalogPage;
use HfH\Catalog\Admin\Page\FilterPage;
use HfH\Catalog\Plugin;
use HfH\Catalog\Repository\Filters;
use Override;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class FiltersListTable extends SortableListTable
{
    private object $catalog;
    private array $filters;
    private array $options_by_filter;

    public function __construct(object $catalog, array $filters, array $options_by_filter)
    {
        parent::__construct([
            'singular' => 'filter',
            'plural'   => 'filters',
            'ajax'     => false,
        ]);
        $this->catalog = $catalog;
        $this->filters = $filters;
        $this->options_by_filter = $options_by_filter;
    }

    #[Override]
    protected function row_id(object $item): int
    {
        return (int) $item->id;
    }

    #[Override]
    protected function sortable_type(): string
    {
        return Filters::SORTABLE_TYPE;
    }

    #[Override]
    protected function sortable_parent(): int
    {
        return (int) $this->catalog->id;
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

        $actions = [
            'edit' => $this->action_link(
                $filter_url,
                __('Edit', Plugin::TEXT_DOMAIN),
                /* translators: %s: filter name */
                sprintf(__('Edit “%s”', Plugin::TEXT_DOMAIN), $filter->name)
            ),
        ];

        $actions['move_up'] = $this->action_link(
            $this->action_url(CatalogPage::ACTION_MOVE_FILTER, $filter, ['direction' => 'up']),
            __('Move up', Plugin::TEXT_DOMAIN),
            /* translators: %s: filter name */
            sprintf(__('Move “%s” up', Plugin::TEXT_DOMAIN), $filter->name)
        );

        $actions['move_down'] = $this->action_link(
            $this->action_url(CatalogPage::ACTION_MOVE_FILTER, $filter, ['direction' => 'down']),
            __('Move down', Plugin::TEXT_DOMAIN),
            /* translators: %s: filter name */
            sprintf(__('Move “%s” down', Plugin::TEXT_DOMAIN), $filter->name)
        );

        $actions['delete'] = $this->action_link(
            $this->action_url(CatalogPage::ACTION_DELETE_FILTER, $filter),
            __('Delete', Plugin::TEXT_DOMAIN),
            /* translators: %s: filter name */
            sprintf(__('Delete “%s”', Plugin::TEXT_DOMAIN), $filter->name),
            'submitdelete',
            /* translators: %s: filter name */
            sprintf(__('Delete “%s” and all its options? This cannot be undone.', Plugin::TEXT_DOMAIN), $filter->name)
        );

        ob_start();
?>
        <strong><a class="row-title" href="<?= esc_url($filter_url) ?>"><?= esc_html($filter->name) ?></a></strong>
        <br>
        <?= esc_html($filter->slug) ?>
        <?= $this->row_actions($actions) ?>
<?php
        return ob_get_clean();
    }

    protected function column_options($filter): string
    {
        $filter_options = $this->options_by_filter[$filter->id] ?? [];
        return $filter_options ? esc_html(implode(', ', wp_list_pluck($filter_options, 'name'))) : '—';
    }

    private function action_url(string $action, object $filter, array $args = []): string
    {
        return CatalogPage::action_url($action, ['id' => $this->catalog->id, 'filter_id' => $filter->id] + $args);
    }

    protected function display_tablenav($which): void
    {
        if ($which === 'top') {
            return;
        }
        parent::display_tablenav($which);
    }
}
