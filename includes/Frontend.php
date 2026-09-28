<?php

namespace HfH\Catalog;

use HfH\Catalog\Repository\Catalogs;
use HfH\Catalog\Repository\SiteFilterOptions;
use HfH\Catalog\Repository\Sites;

/**
 * Turns a /catalogs/{slug}/ request into a rendered catalog page.
 */
class Frontend
{
    const URL_BASE = 'catalogs';
    const QUERY_VAR_SLUG = 'hfh_catalog_slug';
    const QUERY_VAR_INDEX = 'hfh_catalog_index';
    const FILTER_PREFIX = 'hfhcat_';

    private static ?self $instance = null;
    private ?object $catalog = null;
    private bool $is_index = false;

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
        add_action('init', [$this, 'add_rewrite_rules']);
        add_action('parse_query', [$this, 'parse_query']);
        add_action('wp_head', [$this, 'render_canonical']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_filter('pre_handle_404', [$this, 'pre_handle_404'], 10, 2);
        add_filter('template_include', [$this, 'template_include']);
        add_filter('document_title_parts', [$this, 'document_title_parts']);
    }

    /**
     * The public URL of a catalog. Also used by the admin.
     */
    public static function catalog_url(string $slug): string
    {
        return get_home_url(get_main_site_id(), user_trailingslashit(self::URL_BASE . '/' . $slug));
    }

    /**
     * The public URL of the catalog index.
     */
    public static function index_url(): string
    {
        return get_home_url(get_main_site_id(), user_trailingslashit(self::URL_BASE));
    }

    public function get_catalog(): ?object
    {
        return $this->catalog;
    }

    public function add_rewrite_rules(): void
    {
        if (!is_main_site()) {
            return;
        }
        add_rewrite_rule(
            '^' . self::URL_BASE . '/([^/]+)/?$',
            'index.php?' . self::QUERY_VAR_SLUG . '=$matches[1]',
            'top'
        );
        add_rewrite_rule(
            '^' . self::URL_BASE . '/?$',
            'index.php?' . self::QUERY_VAR_INDEX . '=1',
            'top'
        );
    }

    public function add_query_vars(array $public_query_vars): array
    {
        return [...$public_query_vars, self::QUERY_VAR_SLUG, self::QUERY_VAR_INDEX];
    }

    public function parse_query(\WP_Query $query): void
    {
        if (!$query->is_main_query()) {
            return;
        }

        if ($query->get(self::QUERY_VAR_INDEX)) {
            $query->is_home = false;
            $this->is_index = true;
            return;
        }

        $slug = $query->get(self::QUERY_VAR_SLUG);
        if (!$slug) {
            return;
        }

        $query->is_home = false;
        $this->catalog = Catalogs::findBySlug($slug);

        if (!$this->catalog) {
            $query->set_404();
            status_header(404);
            nocache_headers();
        }
    }

    public function pre_handle_404(bool $preempt, \WP_Query $query): bool
    {
        return ($this->catalog || $this->is_index) ? true : $preempt;
    }

    public function template_include(string $template): string
    {
        if ($this->is_index) {
            return HFH_CATALOG_PLUGIN_DIR . 'templates/catalogs.php';
        }
        return $this->catalog ? HFH_CATALOG_PLUGIN_DIR . 'templates/catalog.php' : $template;
    }

    public function document_title_parts(array $parts): array
    {
        if ($this->catalog) {
            $parts['title'] = $this->catalog->name;
        } elseif ($this->is_index) {
            $parts['title'] = __('Catalogs', Plugin::TEXT_DOMAIN);
        }
        return $parts;
    }

    /**
     * Filtered views are the same page, so they all point at the plain URL.
     */
    public function render_canonical(): void
    {
        if (!$this->catalog && !$this->is_index) {
            return;
        }

        printf(
            '<link rel="canonical" href="%s">' . "\n",
            esc_url($this->catalog ? self::catalog_url($this->catalog->slug) : self::index_url())
        );
    }

    public function enqueue_assets(): void
    {
        if (!$this->catalog && !$this->is_index) {
            return;
        }

        wp_enqueue_style(
            'hfh-catalog',
            HFH_CATALOG_PLUGIN_URL . 'assets/catalog.css',
            [],
            HFH_CATALOG_PLUGIN_VERSION
        );

        if (!$this->catalog) {
            return;
        }

        wp_enqueue_script(
            'hfh-catalog',
            HFH_CATALOG_PLUGIN_URL . 'assets/catalog.js',
            [],
            HFH_CATALOG_PLUGIN_VERSION,
            ['in_footer' => true, 'strategy' => 'defer']
        );

        wp_add_inline_script(
            'hfh-catalog',
            'window.hfhCatalog = ' . wp_json_encode([
                'endpoint' => RestApi::results_url($this->catalog->slug),
                'pageUrl'  => self::catalog_url($this->catalog->slug),
            ]) . ';',
            'before'
        );
    }

    /**
     * The query parameter name for a filter.
     */
    public function filter_param(string $filter_slug): string
    {
        return self::FILTER_PREFIX . $filter_slug;
    }

    /**
     * Reads the selected filter options from the query string,
     * keeping only filters and options that exist in this catalog.
     *
     * @param array $filters Filters with their options attached.
     * @return array [filter slug => [option slug, ...]]
     */
    public function selection_from_query(array $filters): array
    {
        $selection = [];

        foreach ($filters as $filter) {
            $submitted = $_GET[$this->filter_param($filter->slug)] ?? null;
            if (!is_array($submitted)) {
                continue;
            }

            $submitted = array_map('sanitize_title', array_map('wp_unslash', $submitted));
            $valid = array_values(array_intersect(wp_list_pluck($filter->options, 'slug'), $submitted));

            if ($valid) {
                $selection[$filter->slug] = $valid;
            }
        }

        return $selection;
    }

    /**
     * The catalog's sites matching the selection, in catalog order.
     */
    public function sites_for_selection(int $catalog_id, array $selection): array
    {
        return Sites::findForCatalog(
            $catalog_id,
            SiteFilterOptions::findSiteIdsForSelection($catalog_id, $selection)
        );
    }

    /**
     * Renders the results partial and returns it.
     */
    public function render_results(array $sites, array $selection): string
    {
        ob_start();
        require HFH_CATALOG_PLUGIN_DIR . 'templates/results.php';
        return ob_get_clean();
    }
}
