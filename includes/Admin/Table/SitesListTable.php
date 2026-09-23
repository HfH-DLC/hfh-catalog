<?php

namespace HfH\Catalog\Admin\Table;

use HfH\Catalog\Admin\Page\CatalogPage;
use HfH\Catalog\Admin\Page\SitePage;
use HfH\Catalog\Plugin;
use HfH\Catalog\Repository\Sites;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * The sites in a catalog, with one column per filter.
 */
class SitesListTable extends \WP_List_Table
{
    private object $catalog;
    private array $filters;
    private array $site_filter_options;

    public function __construct(object $catalog, array $filters, array $site_filter_options)
    {
        parent::__construct([
            'singular' => 'site',
            'plural'   => 'sites',
            'ajax'     => false,
        ]);
        $this->catalog = $catalog;
        $this->filters = $filters;
        $this->site_filter_options = $site_filter_options;
    }

    public function get_columns(): array
    {
        $columns = ['name' => __('Site', Plugin::TEXT_DOMAIN)];
        foreach ($this->filters as $filter) {
            $columns['filter_' . $filter->id] = $filter->name;
        }
        return $columns;
    }

    public function prepare_items(): void
    {
        $this->items = Sites::findAllForCatalog((int) $this->catalog->id);
        $this->_column_headers = [$this->get_columns(), [], []];
    }

    protected function column_name($site): string
    {
        $blog_id = (int) $site->blog_id;
        $edit_url = SitePage::url(['id' => $this->catalog->id, 'blog_id' => $blog_id]);
        $index = array_search($site, $this->items, true);

        $states = [];
        if ($site->archived) {
            $states[] = __('Archived', Plugin::TEXT_DOMAIN);
        }
        if ($site->deleted) {
            $states[] = __('Deactivated', Plugin::TEXT_DOMAIN);
        }

        $actions = [
            'edit' => sprintf(
                '<a href="%s" aria-label="%s">%s</a>',
                esc_url($edit_url),
                /* translators: %s: site name */
                esc_attr(sprintf(__('Edit filter options of “%s”', Plugin::TEXT_DOMAIN), $site->name)),
                esc_html__('Edit', Plugin::TEXT_DOMAIN)
            ),
        ];

        if ($index > 0) {
            $actions['move_up'] = sprintf(
                '<a href="%s" aria-label="%s">%s</a>',
                esc_url($this->action_url(CatalogPage::ACTION_MOVE_SITE, $blog_id, ['direction' => 'up'])),
                /* translators: %s: site name */
                esc_attr(sprintf(__('Move “%s” up', Plugin::TEXT_DOMAIN), $site->name)),
                esc_html__('Move up', Plugin::TEXT_DOMAIN)
            );
        }

        if ($index < count($this->items) - 1) {
            $actions['move_down'] = sprintf(
                '<a href="%s" aria-label="%s">%s</a>',
                esc_url($this->action_url(CatalogPage::ACTION_MOVE_SITE, $blog_id, ['direction' => 'down'])),
                /* translators: %s: site name */
                esc_attr(sprintf(__('Move “%s” down', Plugin::TEXT_DOMAIN), $site->name)),
                esc_html__('Move down', Plugin::TEXT_DOMAIN)
            );
        }

        $actions['remove'] = sprintf(
            '<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
            esc_url($this->action_url(CatalogPage::ACTION_REMOVE_SITE, $blog_id)),
            /* translators: %s: site name */
            esc_attr(sprintf(__('Remove “%s” from this catalog', Plugin::TEXT_DOMAIN), $site->name)),
            esc_html__('Remove', Plugin::TEXT_DOMAIN)
        );

        return sprintf(
            '<strong><a class="row-title" href="%s">%s</a></strong>%s<br><span class="description">%s</span>%s',
            esc_url($edit_url),
            esc_html($site->name),
            $states
                ? ' &mdash; <span class="post-state">' . esc_html(implode(', ', $states)) . '</span>'
                : '',
            $states
                ? esc_html__('Not shown on the public catalog page.', Plugin::TEXT_DOMAIN)
                : esc_html($site->domain . $site->path),
            $this->row_actions($actions)
        );
    }

    /**
     * The filter columns are dynamic, so they all land here.
     */
    protected function column_default($site, $column_name): string
    {
        if (!str_starts_with($column_name, 'filter_')) {
            return '';
        }

        $filter_id = (int) substr($column_name, strlen('filter_'));
        $filter_options = $this->site_filter_options[(int) $site->blog_id][$filter_id] ?? [];

        return $filter_options ? esc_html(implode(', ', wp_list_pluck($filter_options, 'name'))) : '—';
    }

    private function action_url(string $action, int $blog_id, array $args = []): string
    {
        return CatalogPage::action_url($action, ['id' => $this->catalog->id, 'blog_id' => $blog_id] + $args);
    }

    protected function display_tablenav($which): void
    {
        if ($which === 'top') {
            return;
        }
        parent::display_tablenav($which);
    }
}
