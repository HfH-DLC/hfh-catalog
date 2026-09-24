<?php

namespace HfH\Catalog\Admin\Table;

use HfH\Catalog\Admin\Page\CatalogPage;
use HfH\Catalog\Frontend;
use HfH\Catalog\Plugin;
use HfH\Catalog\Repository\Catalogs;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * The catalogs one site appears in, shown in that site's admin.
 */
class SiteCatalogsListTable extends \WP_List_Table
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
        $this->items = Catalogs::findForSite(get_current_blog_id());
        $this->_column_headers = [$this->get_columns(), [], []];
    }

    protected function column_name($catalog): string
    {
        $url = Frontend::catalog_url($catalog->slug);

        $actions = [
            'view' => sprintf(
                '<a href="%s">%s</a>',
                esc_url($url),
                esc_html__('View', Plugin::TEXT_DOMAIN)
            ),
        ];

        if (current_user_can('manage_network_options')) {
            $actions['edit'] = sprintf(
                '<a href="%s">%s</a>',
                esc_url(CatalogPage::url(['id' => $catalog->id])),
                esc_html__('Edit', Plugin::TEXT_DOMAIN)
            );
        }

        return sprintf(
            '<strong><a class="row-title" href="%s">%s</a></strong>%s',
            esc_url($url),
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
