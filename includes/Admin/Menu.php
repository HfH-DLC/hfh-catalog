<?php

namespace HfH\Catalog\Admin;

use HfH\Catalog\Admin\Page\AdminPage;
use HfH\Catalog\Admin\Page\CatalogPage;
use HfH\Catalog\Admin\Page\CatalogsPage;
use HfH\Catalog\Admin\Page\FilterOptionPage;
use HfH\Catalog\Admin\Page\FilterPage;
use HfH\Catalog\Admin\Page\NewCatalogPage;
use HfH\Catalog\Admin\Page\SitePage;
use HfH\Catalog\Plugin;
use HfH\Catalog\RestApi;

/**
 * Registers the Network Admin pages and everything that spans all of them.
 */
class Menu
{
    private static ?self $instance = null;

    private CatalogsPage $catalogs;
    private NewCatalogPage $new_catalog;
    private CatalogPage $catalog;
    private FilterPage $filter;
    private FilterOptionPage $filter_option;
    private SitePage $site;

    public static function get_instance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->catalogs = new CatalogsPage();
        $this->new_catalog = new NewCatalogPage();
        $this->catalog = new CatalogPage();
        $this->filter = new FilterPage();
        $this->filter_option = new FilterOptionPage();
        $this->site = new SitePage();

        add_action('network_admin_menu', [$this, 'register']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_head', [$this, 'hide_pages']);
        add_filter('submenu_file', [$this, 'highlight_submenu']);
        add_filter('removable_query_args', [$this, 'removable_query_args']);
    }

    public function register(): void
    {
        $hook = add_menu_page(
            $this->catalogs->page_title(),
            $this->catalogs->menu_title(),
            AdminPage::CAPABILITY,
            CatalogsPage::SLUG,
            [$this->catalogs, 'render'],
            'none'
        );
        add_action('load-' . $hook, [$this->catalogs, 'load']);

        // Replaces the duplicated top-level entry in the submenu.
        add_submenu_page(
            CatalogsPage::SLUG,
            __('All Catalogs', Plugin::TEXT_DOMAIN),
            __('All Catalogs', Plugin::TEXT_DOMAIN),
            AdminPage::CAPABILITY,
            CatalogsPage::SLUG,
            [$this->catalogs, 'render']
        );

        $this->add_page($this->new_catalog, NewCatalogPage::SLUG);
        $this->add_page($this->catalog, CatalogPage::SLUG);
        $this->add_page($this->filter, FilterPage::SLUG);
        $this->add_page($this->filter_option, FilterOptionPage::SLUG);
        $this->add_page($this->site, SitePage::SLUG);
    }

    private function add_page(AdminPage $page, string $slug): void
    {
        $hook = add_submenu_page(
            CatalogsPage::SLUG,
            $page->page_title(),
            $page->menu_title(),
            AdminPage::CAPABILITY,
            $slug,
            [$page, 'render']
        );
        add_action('load-' . $hook, [$page, 'load']);
    }

    /**
     * Pages reached from a link, not from the menu.
     */
    private function hidden_slugs(): array
    {
        return [CatalogPage::SLUG, FilterPage::SLUG, FilterOptionPage::SLUG, SitePage::SLUG];
    }

    public function hide_pages(): void
    {
        foreach ($this->hidden_slugs() as $slug) {
            remove_submenu_page(CatalogsPage::SLUG, $slug);
        }
    }

    public function highlight_submenu(?string $submenu_file): ?string
    {
        if (in_array($_GET['page'] ?? '', $this->hidden_slugs(), true)) {
            return CatalogsPage::SLUG;
        }
        return $submenu_file;
    }

    public function removable_query_args(array $args): array
    {
        return [
            ...$args,
            ...CatalogsPage::NOTICES,
            ...CatalogPage::NOTICES,
            ...FilterPage::NOTICES,
        ];
    }

    public function enqueue_assets(): void
    {
        if (!is_network_admin()) {
            return;
        }

        wp_enqueue_style(
            'hfh-catalog-admin',
            HFH_CATALOG_PLUGIN_URL . 'assets/admin.css',
            [],
            HFH_CATALOG_PLUGIN_VERSION
        );

        if (!in_array($_GET['page'] ?? '', [CatalogsPage::SLUG, CatalogPage::SLUG, FilterPage::SLUG], true)) {
            return;
        }

        wp_enqueue_script(
            'hfh-catalog-admin-sortable',
            HFH_CATALOG_PLUGIN_URL . 'assets/admin-sortable.js',
            ['jquery-ui-sortable'],
            HFH_CATALOG_PLUGIN_VERSION,
            true
        );

        wp_add_inline_script(
            'hfh-catalog-admin-sortable',
            'window.hfhCatalogSortable = ' . wp_json_encode([
                'restUrl'      => get_rest_url(get_main_site_id(), RestApi::NAMESPACE . RestApi::ROUTE_REORDER),
                'nonce'        => wp_create_nonce('wp_rest'),
                'errorMessage' => __('The order could not be saved. Reload the page and try again.', Plugin::TEXT_DOMAIN),
            ]) . ';',
            'before'
        );
    }
}
