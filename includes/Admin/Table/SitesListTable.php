<?php

namespace HfH\Catalog\Admin\Table;

use HfH\Catalog\Admin\Page\CatalogPage;
use HfH\Catalog\Admin\Page\SitePage;
use HfH\Catalog\Plugin;
use HfH\Catalog\Repository\Sites;
use Override;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * The sites in a catalog, with one column per filter.
 */
class SitesListTable extends SortableListTable
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

    #[Override]
    protected function row_id(object $item): int
    {
        return (int) $item->blog_id;
    }

    #[Override]
    protected function sortable_type(): string
    {
        return Sites::SORTABLE_TYPE;
    }

    #[Override]
    protected function sortable_parent(): int
    {
        return (int) $this->catalog->id;
    }

    public function get_columns(): array
    {
        $columns = ['name' => __('Site', 'hfh-catalog')];
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

        $states = [];
        if ($site->archived) {
            $states[] = __('Archived', 'hfh-catalog');
        }
        if ($site->deleted) {
            $states[] = __('Deactivated', 'hfh-catalog');
        }

        $actions = [
            'edit' => $this->action_link(
                $edit_url,
                __('Edit', 'hfh-catalog'),
                /* translators: %s: site name */
                sprintf(__('Edit filter options of "%s"', 'hfh-catalog'), $site->name)
            ),
        ];

        $actions['move_up'] = $this->action_link(
            $this->action_url(CatalogPage::ACTION_MOVE_SITE, $blog_id, ['direction' => 'up']),
            __('Move up', 'hfh-catalog'),
            /* translators: %s: site name */
            sprintf(__('Move "%s" up', 'hfh-catalog'), $site->name)
        );

        $actions['move_down'] = $this->action_link(
            $this->action_url(CatalogPage::ACTION_MOVE_SITE, $blog_id, ['direction' => 'down']),
            __('Move down', 'hfh-catalog'),
            /* translators: %s: site name */
            sprintf(__('Move "%s" down', 'hfh-catalog'), $site->name)
        );

        $actions['remove'] = $this->action_link(
            $this->action_url(CatalogPage::ACTION_REMOVE_SITE, $blog_id),
            __('Remove', 'hfh-catalog'),
            /* translators: %s: site name */
            sprintf(__('Remove "%s" from this catalog', 'hfh-catalog'), $site->name),
            'submitdelete'
        );

        ob_start();
?>
        <strong><a class="row-title" href="<?= esc_url($edit_url) ?>"><?= esc_html($site->name) ?></a></strong>
        <?php if ($states) : ?>
            &mdash; <span class="post-state"><?= esc_html(implode(', ', $states)) ?></span>
        <?php endif; ?>
        <br>
        <span class="description">
            <?php if ($states) : ?>
                <?php esc_html_e('Not shown on the public catalog page.', 'hfh-catalog'); ?>
            <?php else : ?>
                <?= esc_html($site->domain . $site->path) ?>
            <?php endif; ?>
        </span>
        <?= $this->row_actions($actions) ?>
<?php
        return ob_get_clean();
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
