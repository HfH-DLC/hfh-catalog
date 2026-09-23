<?php

namespace HfH\Catalog\Repository;

use HfH\Catalog\DB;

class FilterOptions
{
    public static function findForFilter(int $filter_id): array
    {
        global $wpdb;
        $table = DB::filterOptionsTable();
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE filter_id = %d ORDER BY sort_order, id",
            $filter_id
        ));
    }

    /**
     * All filter options of a catalog, grouped by filter id.
     */
    public static function findForCatalog(int $catalog_id): array
    {
        global $wpdb;
        $table = DB::filterOptionsTable();
        $filters = DB::filtersTable();

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT o.* FROM {$table} o
            JOIN {$filters} f ON f.id = o.filter_id
            WHERE f.catalog_id = %d
            ORDER BY o.sort_order, o.id",
            $catalog_id
        ));

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row->filter_id][] = $row;
        }
        return $grouped;
    }

    /**
     * Flat list of the catalog's filter option ids.
     */
    public static function findIdsForCatalog(int $catalog_id): array
    {
        $ids = [];
        foreach (self::findForCatalog($catalog_id) as $filter_options) {
            foreach ($filter_options as $filter_option) {
                $ids[] = (int) $filter_option->id;
            }
        }
        return $ids;
    }

    public static function findById(int $id): ?object
    {
        global $wpdb;
        $table = DB::filterOptionsTable();
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id)) ?: null;
    }

    public static function slugExists(int $filter_id, string $slug, int $exclude_id = 0): bool
    {
        global $wpdb;
        $table = DB::filterOptionsTable();
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE filter_id = %d AND slug = %s AND id != %d",
            $filter_id,
            $slug,
            $exclude_id
        ));
    }

    public static function create(int $filter_id, string $name, string $slug): bool
    {
        global $wpdb;
        return (bool) $wpdb->insert(
            DB::filterOptionsTable(),
            ['filter_id' => $filter_id, 'name' => $name, 'slug' => $slug],
            ['%d', '%s', '%s']
        );
    }

    public static function update(int $filter_option_id, string $name, string $slug): bool
    {
        global $wpdb;
        return $wpdb->update(
            DB::filterOptionsTable(),
            ['name' => $name, 'slug' => $slug],
            ['id' => $filter_option_id],
            ['%s', '%s'],
            ['%d']
        ) !== false;
    }

    /**
     * Deletes the filter option and its assignments.
     */
    public static function delete(int $filter_id, int $filter_option_id): bool
    {
        global $wpdb;

        $deleted = $wpdb->delete(
            DB::filterOptionsTable(),
            ['id' => $filter_option_id, 'filter_id' => $filter_id],
            ['%d', '%d']
        );
        if ($deleted === false) {
            return false;
        }
        if ($deleted === 0) {
            return true;
        }

        return $wpdb->delete(
            DB::siteFilterOptionsTable(),
            ['filter_option_id' => $filter_option_id],
            ['%d']
        ) !== false;
    }

    public static function move(int $filter_id, int $filter_option_id, int $offset): bool
    {
        return DB::reorder(
            DB::filterOptionsTable(),
            wp_list_pluck(self::findForFilter($filter_id), 'id'),
            $filter_option_id,
            $offset
        );
    }
}
