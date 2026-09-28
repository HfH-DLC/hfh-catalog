<?php

namespace HfH\Catalog\Repository;

use HfH\Catalog\DB;

class Catalogs
{
    const SORTABLE_TYPE = 'catalogs';

    /**
     * Every catalog: the ones on the index first, in their order, then the rest by name.
     */
    public static function findAll(): array
    {
        global $wpdb;
        $table = DB::catalogsTable();
        return $wpdb->get_results("SELECT * FROM {$table} ORDER BY listed DESC, sort_order, name");
    }

    /**
     * The catalogs chosen for the public index, in the same order.
     */
    public static function findListed(): array
    {
        global $wpdb;
        $table = DB::catalogsTable();
        return $wpdb->get_results("SELECT * FROM {$table} WHERE listed = 1 ORDER BY sort_order, name");
    }

    public static function setListed(int $catalog_id, bool $listed): bool
    {
        global $wpdb;
        $table = DB::catalogsTable();

        // Listing puts it last on the index; unlisting drops its position.
        $sort_order = $listed
            ? 1 + (int) $wpdb->get_var("SELECT MAX(sort_order) FROM {$table} WHERE listed = 1")
            : 0;

        return $wpdb->update(
            $table,
            ['listed' => $listed ? 1 : 0, 'sort_order' => $sort_order],
            ['id' => $catalog_id],
            ['%d', '%d'],
            ['%d']
        ) !== false;
    }

    /**
     * Only listed catalogs have an order, so only they are reordered.
     */
    public static function move(int $catalog_id, int $offset): bool
    {
        return DB::reorder(
            DB::catalogsTable(),
            wp_list_pluck(self::findListed(), 'id'),
            $catalog_id,
            $offset
        );
    }

    /**
     * Applies a new order to the listed catalogs.
     *
     * @param array $ids All listed catalog ids, in the wanted order.
     */
    public static function reorder(array $ids): bool
    {
        $current = wp_list_pluck(self::findListed(), 'id');
        if (!DB::isSameSet($current, $ids)) {
            return false;
        }

        return DB::applyOrder(DB::catalogsTable(), $ids);
    }

    public static function findById(int $id): ?object
    {
        global $wpdb;
        $table = DB::catalogsTable();
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id)) ?: null;
    }

    public static function findBySlug(string $slug): ?object
    {
        global $wpdb;
        $table = DB::catalogsTable();
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE slug = %s", $slug)) ?: null;
    }

    /**
     * The catalogs a site belongs to, by name.
     */
    public static function findForSite(int $blog_id): array
    {
        global $wpdb;
        $catalogs = DB::catalogsTable();
        $catalog_sites = DB::catalogSitesTable();

        return $wpdb->get_results($wpdb->prepare(
            "SELECT c.* FROM {$catalogs} c
            JOIN {$catalog_sites} cs ON cs.catalog_id = c.id
            WHERE cs.blog_id = %d
            ORDER BY c.name",
            $blog_id
        ));
    }

    public static function slugExists(string $slug, int $exclude_id = 0): bool
    {
        global $wpdb;
        $table = DB::catalogsTable();
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE slug = %s AND id != %d",
            $slug,
            $exclude_id
        ));
    }

    /**
     * Appends -2, -3, ... until the slug is free.
     */
    public static function uniqueSlug(string $slug, int $exclude_id = 0): string
    {
        $suffix = 2;
        while (self::slugExists("{$slug}-{$suffix}", $exclude_id)) {
            $suffix++;
        }
        return "{$slug}-{$suffix}";
    }

    public static function create(string $name, string $slug): bool
    {
        global $wpdb;
        return (bool) $wpdb->insert(
            DB::catalogsTable(),
            ['name' => $name, 'slug' => $slug],
            ['%s', '%s']
        );
    }

    public static function update(int $catalog_id, string $name, string $slug, string $description): bool
    {
        global $wpdb;
        return $wpdb->update(
            DB::catalogsTable(),
            ['name' => $name, 'slug' => $slug, 'description' => $description],
            ['id' => $catalog_id],
            ['%s', '%s', '%s'],
            ['%d']
        ) !== false;
    }

    /**
     * Deletes the catalog with its filters, filter options and assignments.
     */
    public static function delete(int $catalog_id): bool
    {
        global $wpdb;
        $site_filter_options = DB::siteFilterOptionsTable();
        $filter_options = DB::filterOptionsTable();
        $filters = DB::filtersTable();
        $catalog_sites = DB::catalogSitesTable();
        $catalogs = DB::catalogsTable();

        return DB::transaction([
            $wpdb->prepare(
                "DELETE sfo FROM {$site_filter_options} sfo
                JOIN {$filter_options} o ON o.id = sfo.filter_option_id
                JOIN {$filters} f ON f.id = o.filter_id
                WHERE f.catalog_id = %d",
                $catalog_id
            ),
            $wpdb->prepare(
                "DELETE o FROM {$filter_options} o
                JOIN {$filters} f ON f.id = o.filter_id
                WHERE f.catalog_id = %d",
                $catalog_id
            ),
            $wpdb->prepare("DELETE FROM {$filters} WHERE catalog_id = %d", $catalog_id),
            $wpdb->prepare("DELETE FROM {$catalog_sites} WHERE catalog_id = %d", $catalog_id),
            $wpdb->prepare("DELETE FROM {$catalogs} WHERE id = %d", $catalog_id),
        ]);
    }
}
