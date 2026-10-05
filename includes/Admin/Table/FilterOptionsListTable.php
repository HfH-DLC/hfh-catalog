<?php

namespace HfH\Catalog\Admin\Table;

use HfH\Catalog\Admin\Page\FilterOptionPage;
use HfH\Catalog\Admin\Page\FilterPage;
use HfH\Catalog\Plugin;
use HfH\Catalog\Repository\FilterOptions;
use Override;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class FilterOptionsListTable extends SortableListTable
{
    private object $filter;
    private array $filter_options;

    public function __construct(object $filter, array $filter_options)
    {
        parent::__construct([
            'singular' => 'filter_option',
            'plural'   => 'filter_options',
            'ajax'     => false,
        ]);
        $this->filter = $filter;
        $this->filter_options = $filter_options;
    }

    #[Override]
    protected function row_id(object $item): int
    {
        return (int) $item->id;
    }

    #[Override]
    protected function sortable_type(): string
    {
        return FilterOptions::SORTABLE_TYPE;
    }

    #[Override]
    protected function sortable_parent(): int
    {
        return (int) $this->filter->id;
    }

    public function get_columns(): array
    {
        return ['name' => __('Option', 'hfh-catalog')];
    }

    public function prepare_items(): void
    {
        $this->items = $this->filter_options;
        $this->_column_headers = [$this->get_columns(), [], []];
    }

    protected function column_name($filter_option): string
    {
        $edit_url = FilterOptionPage::url(['id' => $filter_option->id]);

        $actions = [
            'edit' => $this->action_link(
                $edit_url,
                __('Edit', 'hfh-catalog'),
                /* translators: %s: filter option name */
                sprintf(__('Edit "%s"', 'hfh-catalog'), $filter_option->name)
            ),
        ];

        $actions['move_up'] = $this->action_link(
            $this->action_url(FilterPage::ACTION_MOVE_OPTION, $filter_option, ['direction' => 'up']),
            __('Move up', 'hfh-catalog'),
            /* translators: %s: filter option name */
            sprintf(__('Move "%s" up', 'hfh-catalog'), $filter_option->name)
        );

        $actions['move_down'] = $this->action_link(
            $this->action_url(FilterPage::ACTION_MOVE_OPTION, $filter_option, ['direction' => 'down']),
            __('Move down', 'hfh-catalog'),
            /* translators: %s: filter option name */
            sprintf(__('Move "%s" down', 'hfh-catalog'), $filter_option->name)
        );

        $actions['delete'] = $this->action_link(
            $this->action_url(FilterPage::ACTION_DELETE_OPTION, $filter_option),
            __('Delete', 'hfh-catalog'),
            /* translators: %s: filter option name */
            sprintf(__('Delete "%s"', 'hfh-catalog'), $filter_option->name),
            'submitdelete',
            /* translators: %s: filter option name */
            sprintf(__('Delete "%s"?', 'hfh-catalog'), $filter_option->name)
        );

        ob_start();
?>
        <strong><a class="row-title" href="<?= esc_url($edit_url) ?>"><?= esc_html($filter_option->name) ?></a></strong>
        <?= $this->row_actions($actions) ?>
<?php
        return ob_get_clean();
    }

    private function action_url(string $action, object $filter_option, array $args = []): string
    {
        return FilterPage::action_url(
            $action,
            ['id' => $this->filter->id, 'filter_option_id' => $filter_option->id] + $args
        );
    }

    protected function display_tablenav($which): void
    {
        if ($which === 'top') {
            return;
        }
        parent::display_tablenav($which);
    }
}
