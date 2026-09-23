<?php

namespace HfH\Catalog\Admin\Table;

use HfH\Catalog\Admin\Page\CatalogPage;
use HfH\Catalog\Admin\Page\CatalogsPage;
use HfH\Catalog\Plugin;
use HfH\Catalog\Repository\Catalogs;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class CatalogsListTable extends \WP_List_Table
{
    public function __construct()
    {
        parent::__construct([
            'singular' => 'catalog',
            'plural'   => 'catalogs',
            'ajax'     => false,
        ]);
    }

    public function get_columns(): array
    {
        return [
            'name' => __('Name', Plugin::TEXT_DOMAIN),
            'slug' => __('Slug', Plugin::TEXT_DOMAIN),
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
        $delete_url = CatalogsPage::action_url(CatalogsPage::ACTION_DELETE, ['catalog_id' => $catalog->id]);

        $actions = [
            'edit' => sprintf(
                '<a href="%s" aria-label="%s">%s</a>',
                esc_url($edit_url),
                /* translators: %s: catalog name */
                esc_attr(sprintf(__('Edit “%s”', Plugin::TEXT_DOMAIN), $catalog->name)),
                esc_html__('Edit', Plugin::TEXT_DOMAIN)
            ),
            'delete' => sprintf(
                '<a href="%s" class="submitdelete" aria-label="%s" onclick="return confirm(\'%s\');">%s</a>',
                esc_url($delete_url),
                /* translators: %s: catalog name */
                esc_attr(sprintf(__('Delete “%s”', Plugin::TEXT_DOMAIN), $catalog->name)),
                /* translators: %s: catalog name */
                esc_js(sprintf(__('Delete “%s” with all its filters and site assignments? This cannot be undone.', Plugin::TEXT_DOMAIN), $catalog->name)),
                esc_html__('Delete', Plugin::TEXT_DOMAIN)
            ),
        ];

        return sprintf(
            '<strong><a class="row-title" href="%s">%s</a></strong>%s',
            esc_url($edit_url),
            esc_html($catalog->name),
            $this->row_actions($actions)
        );
    }

    protected function column_slug($catalog): string
    {
        return esc_html($catalog->slug);
    }

    protected function display_tablenav($which): void
    {
        if ($which === 'top') {
            return;
        }
        parent::display_tablenav($which);
    }
}
