<?php

namespace HfH\Catalog\Repository;

use HfH\Catalog\DB;

class Catalogs
{
    public static function findAll(): array
    {
        global $wpdb;
        $table = DB::catalogsTable();
        return $wpdb->get_results("SELECT * FROM {$table} ORDER BY name");
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

    public static function update(int $catalog_id, string $name, string $slug): bool
    {
        global $wpdb;
        return $wpdb->update(
            DB::catalogsTable(),
            ['name' => $name, 'slug' => $slug],
            ['id' => $catalog_id],
            ['%s', '%s'],
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
