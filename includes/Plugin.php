<?php

namespace HfH\Catalog;

use HfH\Catalog\Admin\SiteCatalogs;
use HfH\Catalog\Admin\Menu;
use HfH\Catalog\Repository\SiteFilterOptions;
use HfH\Catalog\Repository\Sites;

/**
 * Loads the plugin's parts. Everything else lives in its own class.
 */
class Plugin
{
    const TEXT_DOMAIN = 'hfh-catalog';

    private static ?self $instance = null;

    public static function get_instance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->init();
    }

    private function init(): void
    {
        add_action('init', [$this, 'load_textdomain']);
        add_action('admin_init', [DB::class, 'maybe_install']);
        add_action('init', [SitemapProvider::class, 'register']);
        add_action('wp_delete_site', [$this, 'delete_site']);

        Frontend::get_instance();
        RestApi::get_instance();
        Menu::get_instance();
        SiteCatalogs::get_instance();
    }

    public function load_textdomain(): void
    {
        load_plugin_textdomain(
            self::TEXT_DOMAIN,
            false,
            dirname(plugin_basename(HFH__CATALOG_PLUGIN_FILE)) . '/languages'
        );
    }

    /**
     * Removes a site from all catalogs when it is deleted from the network.
     * Not admin-only: sites can also be deleted via WP-CLI.
     */
    public function delete_site(\WP_Site $old_site): void
    {
        $blog_id = (int) $old_site->blog_id;

        SiteFilterOptions::deleteForSite($blog_id);
        Sites::deleteForSite($blog_id);
    }
}
