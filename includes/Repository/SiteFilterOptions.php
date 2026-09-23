<?php

namespace HfH\Catalog\Repository;

use HfH\Catalog\DB;

/**
 * Which filter options are assigned to which site.
 */
class SiteFilterOptions
{
    /**
     * All assignments of a catalog, grouped by blog id and filter id.
     */
    public static function findForCatalog(int $catalog_id): array
    {
        global $wpdb;
        $site_filter_options = DB::siteFilterOptionsTable();
        $filter_options = DB::filterOptionsTable();
        $filters = DB::filtersTable();

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT o.*, sfo.blog_id FROM {$site_filter_options} sfo
            JOIN {$filter_options} o ON o.id = sfo.filter_option_id
            JOIN {$filters} f ON f.id = o.filter_id
            WHERE f.catalog_id = %d
            ORDER BY o.sort_order, o.id",
            $catalog_id
        ));

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row->blog_id][(int) $row->filter_id][] = $row;
        }
        return $grouped;
    }

    /**
     * The filter option ids assigned to one site within one catalog.
     */
    public static function findIdsForSite(int $catalog_id, int $blog_id): array
    {
        $ids = [];
        foreach (self::findForCatalog($catalog_id)[$blog_id] ?? [] as $filter_options) {
            foreach ($filter_options as $filter_option) {
                $ids[] = (int) $filter_option->id;
            }
        }
        return $ids;
    }

    /**
     * Site ids of a catalog matching the selection.
     * Within a filter the options combine with OR, across filters with AND.
     *
     * @param array $selection [filter slug => [option slug, ...]]
     */
    public static function findSiteIdsForSelection(int $catalog_id, array $selection): array
    {
        global $wpdb;

        $site_ids = Sites::findIdsForCatalog($catalog_id);
        if (!$site_ids || !$selection) {
            return $site_ids;
        }

        $site_filter_options = DB::siteFilterOptionsTable();
        $filter_options = DB::filterOptionsTable();
        $filters = DB::filtersTable();

        foreach ($selection as $filter_slug => $option_slugs) {
            if (!$site_ids) {
                return [];
            }

            $site_placeholders = implode(',', array_fill(0, count($site_ids), '%d'));
            $option_placeholders = implode(',', array_fill(0, count($option_slugs), '%s'));

            $site_ids = array_map('intval', $wpdb->get_col($wpdb->prepare(
                "SELECT DISTINCT sfo.blog_id FROM {$site_filter_options} sfo
                JOIN {$filter_options} o ON o.id = sfo.filter_option_id
                JOIN {$filters} f ON f.id = o.filter_id
                WHERE f.catalog_id = %d
                  AND f.slug = %s
                  AND o.slug IN ({$option_placeholders})
                  AND sfo.blog_id IN ({$site_placeholders})",
                $catalog_id,
                $filter_slug,
                ...$option_slugs,
                ...$site_ids
            )));
        }

        return $site_ids;
    }

    /**
     * Replaces a site's assignments within one catalog.
     */
    public static function set(int $catalog_id, int $blog_id, array $filter_option_ids): bool
    {
        global $wpdb;
        $table = DB::siteFilterOptionsTable();

        $wpdb->query('START TRANSACTION');

        if (!self::deleteForCatalogSite($catalog_id, $blog_id)) {
            $wpdb->query('ROLLBACK');
            return false;
        }

        foreach ($filter_option_ids as $filter_option_id) {
            $inserted = $wpdb->insert(
                $table,
                ['filter_option_id' => $filter_option_id, 'blog_id' => $blog_id],
                ['%d', '%d']
            );
            if (!$inserted) {
                $wpdb->query('ROLLBACK');
                return false;
            }
        }

        $wpdb->query('COMMIT');
        return true;
    }

    /**
     * Removes a site's assignments within one catalog only.
     */
    public static function deleteForCatalogSite(int $catalog_id, int $blog_id): bool
    {
        global $wpdb;
        $site_filter_options = DB::siteFilterOptionsTable();
        $filter_options = DB::filterOptionsTable();
        $filters = DB::filtersTable();

        return $wpdb->query($wpdb->prepare(
            "DELETE sfo FROM {$site_filter_options} sfo
            JOIN {$filter_options} o ON o.id = sfo.filter_option_id
            JOIN {$filters} f ON f.id = o.filter_id
            WHERE f.catalog_id = %d AND sfo.blog_id = %d",
            $catalog_id,
            $blog_id
        )) !== false;
    }

    /**
     * Removes a deleted site's assignments everywhere.
     */
    public static function deleteForSite(int $blog_id): void
    {
        global $wpdb;
        $wpdb->delete(DB::siteFilterOptionsTable(), ['blog_id' => $blog_id], ['%d']);
    }
}
