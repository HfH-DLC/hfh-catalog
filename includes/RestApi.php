<?php

namespace HfH\Catalog;

use HfH\Catalog\Repository\Catalogs;
use HfH\Catalog\Repository\Filters;

/**
 * Returns the filtered results list, so the page can update without a reload.
 */
class RestApi
{
    const NAMESPACE = 'hfh-catalog/v1';
    const ROUTE_RESULTS = '/catalogs/(?P<slug>[a-z0-9-]+)/results';

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
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * The endpoint URL for one catalog
     */
    public static function results_url(string $slug): string
    {
        return get_rest_url(get_main_site_id(), self::NAMESPACE . '/catalogs/' . $slug . '/results');
    }

    public function register_routes(): void
    {
        register_rest_route(self::NAMESPACE, self::ROUTE_RESULTS, [
            'methods'             => 'GET',
            'callback'            => [$this, 'results'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function results(\WP_REST_Request $request)
    {
        $catalog = Catalogs::findBySlug($request['slug']);
        if (!$catalog) {
            return new \WP_Error(
                'hfh_catalog_not_found',
                __('Catalog not found.', Plugin::TEXT_DOMAIN),
                ['status' => 404]
            );
        }

        $frontend = Frontend::get_instance();
        $filters = Filters::findWithOptionsForCatalog((int) $catalog->id);
        $selection = $frontend->selection_from_query($filters);
        $sites = $frontend->sites_for_selection((int) $catalog->id, $selection);

        return [
            'count' => count($sites),
            'html'  => $frontend->render_results($sites, $selection),
        ];
    }
}
