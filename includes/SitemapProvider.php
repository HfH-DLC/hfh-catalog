<?php

namespace HfH\Catalog;

use HfH\Catalog\Repository\Catalogs;

/**
 * Adds the catalog pages to core's XML sitemap.
 * The name must be letters only, since core routes sub-sitemaps with ([a-z]+?).
 */
class SitemapProvider extends \WP_Sitemaps_Provider
{
    const NAME = 'hfhcatalogs';
    const OBJECT_TYPE = 'hfhcatalog';

    /**
     * Core only builds sitemaps for the site that serves them,
     * and catalogs live on the main site.
     */
    public static function register(): void
    {
        if (!is_main_site() || !function_exists('wp_register_sitemap_provider')) {
            return;
        }

        wp_register_sitemap_provider(self::NAME, new self());
    }

    public function __construct()
    {
        $this->name = self::NAME;
        $this->object_type = self::OBJECT_TYPE;
    }

    public function get_url_list($page_num, $object_subtype = ''): array
    {
        $urls = [];
        foreach (Catalogs::findAll() as $catalog) {
            $urls[] = ['loc' => Frontend::catalog_url($catalog->slug)];
        }
        return $urls;
    }

    public function get_max_num_pages($object_subtype = ''): int
    {
        return Catalogs::findAll() ? 1 : 0;
    }
}
