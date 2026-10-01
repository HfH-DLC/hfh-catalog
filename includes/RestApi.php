<?php

namespace HfH\Catalog;

use HfH\Catalog\Repository\Catalogs;
use HfH\Catalog\Repository\FilterOptions;
use HfH\Catalog\Repository\Filters;
use HfH\Catalog\Repository\Sites;

/**
 * Returns the filtered results list, so the page can update without a reload,
 * and saves the order of the sortable admin tables.
 */
class RestApi
{
    const NAMESPACE = 'hfh-catalog/v1';
    const ROUTE_RESULTS = '/catalogs/(?P<slug>[a-z0-9-]+)/results';
    const ROUTE_REORDER = '/reorder';

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
    public function results_url(string $slug): string
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

        register_rest_route(self::NAMESPACE, self::ROUTE_REORDER, [
            'methods'             => 'POST',
            'callback'            => [$this, 'reorder'],
            'permission_callback' => fn(): bool => current_user_can('manage_network_options'),
            'args'                => [
                'type'   => [
                    'required' => true,
                    'type'     => 'string',
                    'enum'     => [
                        Catalogs::SORTABLE_TYPE,
                        Sites::SORTABLE_TYPE,
                        Filters::SORTABLE_TYPE,
                        FilterOptions::SORTABLE_TYPE,
                    ],
                ],
                'parent' => [
                    'type'    => 'integer',
                    'minimum' => 0,
                    'default' => 0,
                ],
                'order'  => [
                    'required' => true,
                    'type'     => 'array',
                    'items'    => ['type' => 'integer'],
                ],
            ],
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
        $view = $frontend->view_from_query();
        $sites = $frontend->sites_for_selection((int) $catalog->id, $selection);

        return [
            'count'  => count($sites),
            'status' => $frontend->results_status($sites, $selection),
            'html'   => $frontend->render_results($sites, $selection, $view),
        ];
    }

    /**
     * Saves the full new order of one table. The repositories reject it
     * unless it holds exactly the current rows, e.g. when the page is stale.
     */
    public function reorder(\WP_REST_Request $request)
    {
        $parent = (int) $request['parent'];
        $order = $request['order'];

        $saved = match ($request['type']) {
            Catalogs::SORTABLE_TYPE      => Catalogs::reorder($order),
            Sites::SORTABLE_TYPE         => Sites::reorder($parent, $order),
            Filters::SORTABLE_TYPE       => Filters::reorder($parent, $order),
            FilterOptions::SORTABLE_TYPE => FilterOptions::reorder($parent, $order),
        };

        if (!$saved) {
            return new \WP_Error(
                'hfh_catalog_reorder_failed',
                __('The order could not be saved. Reload the page and try again.', Plugin::TEXT_DOMAIN),
                ['status' => 409]
            );
        }

        return ['saved' => true];
    }
}
