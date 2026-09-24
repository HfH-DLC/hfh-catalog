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
        $listed = (int) $catalog->listed === 1;
        // Only the listed catalogs have an order, so positions are counted among them.
        $listed_items = array_values(array_filter($this->items, fn($item) => (int) $item->listed === 1));
        $index = $listed ? array_search($catalog, $listed_items, true) : false;

        $actions = [
            'edit' => sprintf(
                '<a href="%s" aria-label="%s">%s</a>',
                esc_url($edit_url),
                /* translators: %s: catalog name */
                esc_attr(sprintf(__('Edit “%s”', Plugin::TEXT_DOMAIN), $catalog->name)),
                esc_html__('Edit', Plugin::TEXT_DOMAIN)
            ),
            'listed' => sprintf(
                '<a href="%s" aria-label="%s">%s</a>',
                esc_url($this->action_url(CatalogsPage::ACTION_TOGGLE_LISTED, $catalog, ['listed' => $listed ? '0' : '1'])),
                esc_attr($listed
                    /* translators: %s: catalog name */
                    ? sprintf(__('Hide “%s” from the catalog index', Plugin::TEXT_DOMAIN), $catalog->name)
                    /* translators: %s: catalog name */
                    : sprintf(__('Show “%s” on the catalog index', Plugin::TEXT_DOMAIN), $catalog->name)),
                $listed
                    ? esc_html__('Hide from index', Plugin::TEXT_DOMAIN)
                    : esc_html__('Show on index', Plugin::TEXT_DOMAIN)
            ),
        ];

        if ($listed && $index > 0) {
            $actions['move_up'] = sprintf(
                '<a href="%s" aria-label="%s">%s</a>',
                esc_url($this->action_url(CatalogsPage::ACTION_MOVE, $catalog, ['direction' => 'up'])),
                /* translators: %s: catalog name */
                esc_attr(sprintf(__('Move “%s” up', Plugin::TEXT_DOMAIN), $catalog->name)),
                esc_html__('Move up', Plugin::TEXT_DOMAIN)
            );
        }

        if ($listed && $index < count($listed_items) - 1) {
            $actions['move_down'] = sprintf(
                '<a href="%s" aria-label="%s">%s</a>',
                esc_url($this->action_url(CatalogsPage::ACTION_MOVE, $catalog, ['direction' => 'down'])),
                /* translators: %s: catalog name */
                esc_attr(sprintf(__('Move “%s” down', Plugin::TEXT_DOMAIN), $catalog->name)),
                esc_html__('Move down', Plugin::TEXT_DOMAIN)
            );
        }

        $actions['delete'] = sprintf(
            '<a href="%s" class="submitdelete" aria-label="%s" onclick="return confirm(\'%s\');">%s</a>',
            esc_url($this->action_url(CatalogsPage::ACTION_DELETE, $catalog)),
            /* translators: %s: catalog name */
            esc_attr(sprintf(__('Delete “%s”', Plugin::TEXT_DOMAIN), $catalog->name)),
            /* translators: %s: catalog name */
            esc_js(sprintf(__('Delete “%s” with all its filters and site assignments? This cannot be undone.', Plugin::TEXT_DOMAIN), $catalog->name)),
            esc_html__('Delete', Plugin::TEXT_DOMAIN)
        );

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

    protected function column_listed($catalog): string
    {
        return (int) $catalog->listed === 1
            ? esc_html__('Yes', Plugin::TEXT_DOMAIN)
            :  esc_html__('No', Plugin::TEXT_DOMAIN);
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
