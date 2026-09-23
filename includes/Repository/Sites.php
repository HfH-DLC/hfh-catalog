<?php

namespace HfH\Catalog\Repository;

use HfH\Catalog\DB;

/**
 * Which sites belong to a catalog, and in which order.
 */
class Sites
{
    /**
     * Pressbooks mirrors these into wp_blogmeta via DataCollector\Book,
     * so they are readable network-wide without switching sites.
     */
    const META_TITLE = 'pb_title';
    const META_AUTHORS = 'pb_authors';
    const META_COVER = 'pb_cover_image';
    const META_SHORT_DESCRIPTION = 'pb_about_50';
    const META_LONG_DESCRIPTION = 'pb_about_unlimited';
    const META_URL = 'pb_book_url';

    public static function findIdsForCatalog(int $catalog_id): array
    {
        global $wpdb;
        $table = DB::catalogSitesTable();
        return array_map('intval', $wpdb->get_col($wpdb->prepare(
            "SELECT blog_id FROM {$table} WHERE catalog_id = %d ORDER BY sort_order",
            $catalog_id
        )));
    }

    /**
     * The catalog's sites with their book data, in the catalog's order.
     * Archived and deactivated books are left out.
     *
     * @param array|null $site_ids Limit to these ids (already filtered); null for all.
     */
    public static function findForCatalog(int $catalog_id, ?array $site_ids = null): array
    {
        return self::query($catalog_id, $site_ids, ['archived' => '0', 'deleted' => '0']);
    }

    /**
     * The same, including archived and deactivated books, so the admin can manage them.
     *
     * @param array|null $site_ids Limit to these ids (already filtered); null for all.
     */
    public static function findAllForCatalog(int $catalog_id, ?array $site_ids = null): array
    {
        return self::query($catalog_id, $site_ids, []);
    }

    /**
     * @param array $args Extra get_sites() arguments.
     */
    private static function query(int $catalog_id, ?array $site_ids, array $args): array
    {
        $order = self::findIdsForCatalog($catalog_id);
        $wanted = $site_ids === null ? $order : array_map('intval', $site_ids);
        if (!$wanted) {
            return [];
        }

        // get_sites() ignores the order of site__in, so restore the catalog's order.
        $by_id = [];
        foreach (get_sites($args + ['site__in' => $wanted, 'number' => 0]) as $site) {
            $by_id[(int) $site->blog_id] = $site;
        }

        // One query primes the metadata of every book, so get_site_meta() is free below.
        update_meta_cache('blog', array_keys($by_id));

        $sites = [];
        foreach ($order as $site_id) {
            if (isset($by_id[$site_id])) {
                $sites[] = self::withBookData($by_id[$site_id]);
            }
        }
        return $sites;
    }

    /**
     * A site with the book metadata the catalog shows.
     */
    private static function withBookData(\WP_Site $site): object
    {
        $blog_id = (int) $site->blog_id;
        $meta = fn(string $key): string => (string) get_site_meta($blog_id, $key, true);
        $excerpt = $meta(self::META_SHORT_DESCRIPTION) ?: $meta(self::META_LONG_DESCRIPTION);

        return (object) [
            'blog_id' => $blog_id,
            'name' => $site->blogname,
            'domain' => $site->domain,
            'path' => $site->path,
            'archived' => $site->archived === '1',
            'deleted' => $site->deleted === '1',
            'url' => $meta(self::META_URL) ?: get_home_url($blog_id),
            'title' => $meta(self::META_TITLE) ?: $site->blogname,
            'authors' => $meta(self::META_AUTHORS),
            'cover' => $meta(self::META_COVER),
            'excerpt' => wp_trim_words(wp_strip_all_tags($excerpt), 50),
        ];
    }

    /**
     * One site by id, or null. Wraps core's lookup so callers only talk to this class.
     */
    public static function find(int $blog_id): ?\WP_Site
    {
        return $blog_id ? (get_site($blog_id) ?: null) : null;
    }

    /**
     * Sites that can still be added: not already in the catalog, and never the
     * main site, which hosts the catalog pages themselves.
     */
    public static function findAvailable(int $catalog_id): array
    {
        return get_sites([
            'site__not_in' => [...self::findIdsForCatalog($catalog_id), get_main_site_id()],
            'number'       => 0,
        ]);
    }

    public static function isInCatalog(int $catalog_id, int $blog_id): bool
    {
        return in_array($blog_id, self::findIdsForCatalog($catalog_id), true);
    }

    public static function add(int $catalog_id, int $blog_id): bool
    {
        global $wpdb;
        return (bool) $wpdb->insert(
            DB::catalogSitesTable(),
            ['catalog_id' => $catalog_id, 'blog_id' => $blog_id],
            ['%d', '%d']
        );
    }

    /**
     * Removes the site from the catalog, including its assignments there.
     */
    public static function remove(int $catalog_id, int $blog_id): bool
    {
        global $wpdb;

        if (!SiteFilterOptions::deleteForCatalogSite($catalog_id, $blog_id)) {
            return false;
        }

        return $wpdb->delete(
            DB::catalogSitesTable(),
            ['catalog_id' => $catalog_id, 'blog_id' => $blog_id],
            ['%d', '%d']
        ) !== false;
    }

    /**
     * catalog_sites has no id column, so this cannot use DB::reorder().
     */
    public static function move(int $catalog_id, int $blog_id, int $offset): bool
    {
        global $wpdb;
        $table = DB::catalogSitesTable();

        $ids = self::findIdsForCatalog($catalog_id);
        $index = array_search($blog_id, $ids, true);
        $target = $index === false ? false : $index + $offset;

        if ($index === false || !isset($ids[$target])) {
            return true;
        }

        [$ids[$index], $ids[$target]] = [$ids[$target], $ids[$index]];

        foreach ($ids as $position => $id) {
            $updated = $wpdb->update(
                $table,
                ['sort_order' => $position],
                ['catalog_id' => $catalog_id, 'blog_id' => $id],
                ['%d'],
                ['%d', '%d']
            );
            if ($updated === false) {
                return false;
            }
        }
        return true;
    }

    /**
     * Removes a deleted site from every catalog.
     */
    public static function deleteForSite(int $blog_id): void
    {
        global $wpdb;
        $wpdb->delete(DB::catalogSitesTable(), ['blog_id' => $blog_id], ['%d']);
    }
}
