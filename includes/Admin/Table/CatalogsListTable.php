<?php

namespace HfH\Catalog\Admin\Table;

use HfH\Catalog\Admin\Page\CatalogPage;
use HfH\Catalog\Admin\Page\CatalogsPage;
use HfH\Catalog\Frontend;
use HfH\Catalog\Plugin;
use HfH\Catalog\Repository\Catalogs;
use Override;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class CatalogsListTable extends SortableListTable
{
    public function __construct()
    {
        parent::__construct([
            'singular' => 'catalog',
            'plural'   => 'catalogs',
            'ajax'     => false,
        ]);
    }

    #[Override]
    protected function row_id(object $item): int
    {
        return (int) $item->id;
    }

    #[Override]
    protected function sortable_type(): string
    {
        return Catalogs::SORTABLE_TYPE;
    }

    /**
     * Only the listed catalogs have an order.
     */
    #[Override]
    protected function is_sortable_row(object $item): bool
    {
        return (int) $item->listed === 1;
    }

    public function get_columns(): array
    {
        return [
            'name' => __('Name', Plugin::TEXT_DOMAIN),
            'slug' => __('Slug', Plugin::TEXT_DOMAIN),
            'listed' => __('On index', Plugin::TEXT_DOMAIN),
        ];
    }

    public function prepare_items(): void
    {
        $this->items = Catalogs::findAll();
        $this->_column_headers = [$this->get_columns(), [], []];
    }

    protected function column_name($catalog): string
    {
        $edit_url = CatalogPage::url(['id' => $catalog->id]);
        $listed = $this->is_sortable_row($catalog);

        $actions = [
            'edit' => $this->action_link(
                $edit_url,
                __('Edit', Plugin::TEXT_DOMAIN),
                /* translators: %s: catalog name */
                sprintf(__('Edit “%s”', Plugin::TEXT_DOMAIN), $catalog->name)
            ),
            'view' => $this->action_link(
                Frontend::get_instance()->catalog_url($catalog->slug),
                __('View', Plugin::TEXT_DOMAIN),
                /* translators: %s: catalog name */
                sprintf(__('View “%s”', Plugin::TEXT_DOMAIN), $catalog->name),
                '',
                '',
                true
            ),
            'listed' => $this->action_link(
                $this->action_url(CatalogsPage::ACTION_TOGGLE_LISTED, $catalog, ['listed' => $listed ? '0' : '1']),
                $listed
                    ? __('Hide from index', Plugin::TEXT_DOMAIN)
                    : __('Show on index', Plugin::TEXT_DOMAIN),
                $listed
                    /* translators: %s: catalog name */
                    ? sprintf(__('Hide “%s” from the catalog index', Plugin::TEXT_DOMAIN), $catalog->name)
                    /* translators: %s: catalog name */
                    : sprintf(__('Show “%s” on the catalog index', Plugin::TEXT_DOMAIN), $catalog->name)
            ),
        ];

        if ($listed) {
            $actions['move_up'] = $this->action_link(
                $this->action_url(CatalogsPage::ACTION_MOVE, $catalog, ['direction' => 'up']),
                __('Move up', Plugin::TEXT_DOMAIN),
                /* translators: %s: catalog name */
                sprintf(__('Move “%s” up', Plugin::TEXT_DOMAIN), $catalog->name)
            );

            $actions['move_down'] = $this->action_link(
                $this->action_url(CatalogsPage::ACTION_MOVE, $catalog, ['direction' => 'down']),
                __('Move down', Plugin::TEXT_DOMAIN),
                /* translators: %s: catalog name */
                sprintf(__('Move “%s” down', Plugin::TEXT_DOMAIN), $catalog->name)
            );
        }

        $actions['delete'] = $this->action_link(
            $this->action_url(CatalogsPage::ACTION_DELETE, $catalog),
            __('Delete', Plugin::TEXT_DOMAIN),
            /* translators: %s: catalog name */
            sprintf(__('Delete “%s”', Plugin::TEXT_DOMAIN), $catalog->name),
            'submitdelete',
            /* translators: %s: catalog name */
            sprintf(__('Delete “%s” with all its filters and site assignments? This cannot be undone.', Plugin::TEXT_DOMAIN), $catalog->name)
        );

        ob_start();
?>
        <strong><a class="row-title" href="<?= esc_url($edit_url) ?>"><?= esc_html($catalog->name) ?></a></strong>
        <?= $this->row_actions($actions) ?>
<?php
        return ob_get_clean();
    }

    protected function column_slug($catalog): string
    {
        return esc_html($catalog->slug);
    }

    protected function column_listed($catalog): string
    {
        return $this->is_sortable_row($catalog)
            ? esc_html__('Yes', Plugin::TEXT_DOMAIN)
            : esc_html__('No', Plugin::TEXT_DOMAIN);
    }

    private function action_url(string $action, object $catalog, array $args = []): string
    {
        return CatalogsPage::action_url($action, ['catalog_id' => $catalog->id] + $args);
    }

    protected function display_tablenav($which): void
    {
        if ($which === 'top') {
            return;
        }
        parent::display_tablenav($which);
    }
}
