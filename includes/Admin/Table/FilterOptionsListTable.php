<?php

namespace HfH\Catalog\Admin\Table;

use HfH\Catalog\Admin\Page\FilterOptionPage;
use HfH\Catalog\Admin\Page\FilterPage;
use HfH\Catalog\Plugin;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class FilterOptionsListTable extends \WP_List_Table
{
    private array $filter_options;

    public function __construct(array $filter_options)
    {
        parent::__construct([
            'singular' => 'filter_option',
            'plural'   => 'filter_options',
            'ajax'     => false,
        ]);
        $this->filter_options = $filter_options;
    }

    public function get_columns(): array
    {
        return ['name' => __('Option', Plugin::TEXT_DOMAIN)];
    }

    public function prepare_items(): void
    {
        $this->items = $this->filter_options;
        $this->_column_headers = [$this->get_columns(), [], []];
    }

    protected function column_name($filter_option): string
    {
        $edit_url = FilterOptionPage::url(['id' => $filter_option->id]);
        $index = array_search($filter_option, $this->filter_options, true);

        $actions = [
            'edit' => sprintf(
                '<a href="%s" aria-label="%s">%s</a>',
                esc_url($edit_url),
                /* translators: %s: filter option name */
                esc_attr(sprintf(__('Edit “%s”', Plugin::TEXT_DOMAIN), $filter_option->name)),
                esc_html__('Edit', Plugin::TEXT_DOMAIN)
            ),
        ];

        if ($index > 0) {
            $actions['move_up'] = sprintf(
                '<a href="%s" aria-label="%s">%s</a>',
                esc_url($this->action_url(FilterPage::ACTION_MOVE_OPTION, $filter_option, ['direction' => 'up'])),
                /* translators: %s: filter option name */
                esc_attr(sprintf(__('Move “%s” up', Plugin::TEXT_DOMAIN), $filter_option->name)),
                esc_html__('Move up', Plugin::TEXT_DOMAIN)
            );
        }

        if ($index < count($this->filter_options) - 1) {
            $actions['move_down'] = sprintf(
                '<a href="%s" aria-label="%s">%s</a>',
                esc_url($this->action_url(FilterPage::ACTION_MOVE_OPTION, $filter_option, ['direction' => 'down'])),
                /* translators: %s: filter option name */
                esc_attr(sprintf(__('Move “%s” down', Plugin::TEXT_DOMAIN), $filter_option->name)),
                esc_html__('Move down', Plugin::TEXT_DOMAIN)
            );
        }

        $actions['delete'] = sprintf(
            '<a href="%s" class="submitdelete" aria-label="%s" onclick="return confirm(\'%s\');">%s</a>',
            esc_url($this->action_url(FilterPage::ACTION_DELETE_OPTION, $filter_option)),
            /* translators: %s: filter option name */
            esc_attr(sprintf(__('Delete “%s”', Plugin::TEXT_DOMAIN), $filter_option->name)),
            /* translators: %s: filter option name */
            esc_js(sprintf(__('Delete “%s”? This cannot be undone.', Plugin::TEXT_DOMAIN), $filter_option->name)),
            esc_html__('Delete', Plugin::TEXT_DOMAIN)
        );

        return sprintf(
            '<strong><a class="row-title" href="%s">%s</a></strong>%s',
            esc_url($edit_url),
            esc_html($filter_option->name),
            $this->row_actions($actions)
        );
    }

    private function action_url(string $action, object $filter_option, array $args = []): string
    {
        return FilterPage::action_url(
            $action,
            ['id' => $filter_option->filter_id, 'filter_option_id' => $filter_option->id] + $args
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
