<?php

namespace HfH\Catalog;

/**
 * Schema and shared low-level helpers.
 * The queries themselves live in the repositories.
 */
class DB
{
    const DB_VERSION = '1';
    const OPTION_DB_VERSION = 'hfh_catalog_db_version';

    const TABLE_CATALOGS = 'hfh_catalogs';
    const TABLE_SITES = 'hfh_catalog_sites';
    const TABLE_FILTERS = 'hfh_catalog_filters';
    const TABLE_FILTER_OPTIONS = 'hfh_catalog_filter_options';
    const TABLE_SITE_FILTER_OPTIONS = 'hfh_catalog_site_filter_options';

    /* -----------------------------------------------------------------
     * Installation
     * ----------------------------------------------------------------- */

    public static function maybe_install(): void
    {
        if (get_site_option(self::OPTION_DB_VERSION) !== self::DB_VERSION) {
            self::install();
        }
    }

    public static function install(): void
    {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $catalogs = self::catalogsTable();
        dbDelta("CREATE TABLE {$catalogs} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(191) NOT NULL,
            description longtext NULL,
            listed tinyint(1) NOT NULL DEFAULT 0,
            sort_order int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug)
        ) {$charset};");

        $catalog_sites = self::catalogSitesTable();
        dbDelta("CREATE TABLE {$catalog_sites} (
            catalog_id bigint(20) unsigned NOT NULL,
            blog_id bigint(20) NOT NULL,
            sort_order int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY  (catalog_id,blog_id),
            KEY blog_id (blog_id)
        ) {$charset};");

        $filters = self::filtersTable();
        dbDelta("CREATE TABLE {$filters} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            catalog_id bigint(20) unsigned NOT NULL,
            name varchar(255) NOT NULL,
            slug varchar(191) NOT NULL,
            sort_order int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            UNIQUE KEY catalog_slug (catalog_id,slug)
        ) {$charset};");

        $filter_options = self::filterOptionsTable();
        dbDelta("CREATE TABLE {$filter_options} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            filter_id bigint(20) unsigned NOT NULL,
            name varchar(255) NOT NULL,
            slug varchar(191) NOT NULL,
            sort_order int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            UNIQUE KEY filter_slug (filter_id,slug)
        ) {$charset};");

        $site_filter_options = self::siteFilterOptionsTable();
        dbDelta("CREATE TABLE {$site_filter_options} (
            filter_option_id bigint(20) unsigned NOT NULL,
            blog_id bigint(20) NOT NULL,
            PRIMARY KEY  (filter_option_id,blog_id),
            KEY blog_id (blog_id)
        ) {$charset};");

        update_site_option(self::OPTION_DB_VERSION, self::DB_VERSION);
    }

    /* -----------------------------------------------------------------
     * Table names
     * ----------------------------------------------------------------- */

    public static function catalogsTable(): string
    {
        return self::table(self::TABLE_CATALOGS);
    }

    public static function catalogSitesTable(): string
    {
        return self::table(self::TABLE_SITES);
    }

    public static function filtersTable(): string
    {
        return self::table(self::TABLE_FILTERS);
    }

    public static function filterOptionsTable(): string
    {
        return self::table(self::TABLE_FILTER_OPTIONS);
    }

    public static function siteFilterOptionsTable(): string
    {
        return self::table(self::TABLE_SITE_FILTER_OPTIONS);
    }

    private static function table(string $name): string
    {
        global $wpdb;
        return $wpdb->base_prefix . $name;
    }

    /* -----------------------------------------------------------------
     * Shared helpers, used by the repositories
     * ----------------------------------------------------------------- */

    /**
     * Swaps a row with its neighbour and rewrites sort_order for the whole list.
     * Only for tables with an "id" column.
     *
     * @param array $ids Row ids in their current order.
     */
    public static function reorder(string $table, array $ids, int $id, int $offset): bool
    {
        global $wpdb;

        $ids = array_map('intval', $ids);
        $index = array_search($id, $ids, true);
        $target = $index === false ? false : $index + $offset;

        if ($index === false || !isset($ids[$target])) {
            return true;
        }

        [$ids[$index], $ids[$target]] = [$ids[$target], $ids[$index]];

        foreach ($ids as $position => $row_id) {
            if ($wpdb->update($table, ['sort_order' => $position], ['id' => $row_id], ['%d'], ['%d']) === false) {
                return false;
            }
        }
        return true;
    }

    /**
     * Runs prepared queries as one transaction, rolling back on the first failure.
     */
    public static function transaction(array $queries): bool
    {
        global $wpdb;

        $wpdb->query('START TRANSACTION');
        foreach ($queries as $query) {
            if ($wpdb->query($query) === false) {
                $wpdb->query('ROLLBACK');
                return false;
            }
        }
        $wpdb->query('COMMIT');
        return true;
    }
}
