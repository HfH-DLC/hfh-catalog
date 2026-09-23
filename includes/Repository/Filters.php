<?php

namespace HfH\Catalog\Repository;

use HfH\Catalog\DB;

class Filters
{
    public static function findForCatalog(int $catalog_id): array
    {
        global $wpdb;
        $table = DB::filtersTable();
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE catalog_id = %d ORDER BY sort_order, id",
            $catalog_id
        ));
    }

    /**
     * The catalog's filters, each with its options attached as ->options.
     */
    public static function findWithOptionsForCatalog(int $catalog_id): array
    {
        $options_by_filter = FilterOptions::findForCatalog($catalog_id);
        $filters = [];

        foreach (self::findForCatalog($catalog_id) as $filter) {
            $filter->options = $options_by_filter[$filter->id] ?? [];
            $filters[] = $filter;
        }

        return $filters;
    }

    public static function findById(int $id): ?object
    {
        global $wpdb;
        $table = DB::filtersTable();
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id)) ?: null;
    }

    public static function slugExists(int $catalog_id, string $slug, int $exclude_id = 0): bool
    {
        global $wpdb;
        $table = DB::filtersTable();
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE catalog_id = %d AND slug = %s AND id != %d",
            $catalog_id,
            $slug,
            $exclude_id
        ));
    }

    public static function create(int $catalog_id, string $name, string $slug): bool
    {
        global $wpdb;
        return (bool) $wpdb->insert(
            DB::filtersTable(),
            ['catalog_id' => $catalog_id, 'name' => $name, 'slug' => $slug],
            ['%d', '%s', '%s']
        );
    }

    public static function update(int $filter_id, string $name, string $slug): bool
    {
        global $wpdb;
        return $wpdb->update(
            DB::filtersTable(),
            ['name' => $name, 'slug' => $slug],
            ['id' => $filter_id],
            ['%s', '%s'],
            ['%d']
        ) !== false;
    }

    /**
     * Deletes the filter with its options and their assignments.
     */
    public static function delete(int $catalog_id, int $filter_id): bool
    {
        global $wpdb;
        $site_filter_options = DB::siteFilterOptionsTable();
        $filter_options = DB::filterOptionsTable();
        $filters = DB::filtersTable();

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$filters} WHERE id = %d AND catalog_id = %d",
            $filter_id,
            $catalog_id
        ));
        if (!$exists) {
            return true;
        }

        return DB::transaction([
            $wpdb->prepare(
                "DELETE sfo FROM {$site_filter_options} sfo
                JOIN {$filter_options} o ON o.id = sfo.filter_option_id
                WHERE o.filter_id = %d",
                $filter_id
            ),
            $wpdb->prepare("DELETE FROM {$filter_options} WHERE filter_id = %d", $filter_id),
            $wpdb->prepare("DELETE FROM {$filters} WHERE id = %d", $filter_id),
        ]);
    }

    public static function move(int $catalog_id, int $filter_id, int $offset): bool
    {
        return DB::reorder(
            DB::filtersTable(),
            wp_list_pluck(self::findForCatalog($catalog_id), 'id'),
            $filter_id,
            $offset
        );
    }
}
