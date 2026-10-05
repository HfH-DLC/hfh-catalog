<?php

namespace HfH\Catalog\Admin\Page;

use HfH\Catalog\Frontend;
use HfH\Catalog\Admin\Table\FiltersListTable;
use HfH\Catalog\Admin\Table\SitesListTable;
use HfH\Catalog\Admin\Table\SortableListTable;
use HfH\Catalog\Plugin;
use HfH\Catalog\Repository\Catalogs;
use HfH\Catalog\Repository\FilterOptions;
use HfH\Catalog\Repository\Filters;
use HfH\Catalog\Repository\SiteFilterOptions;
use HfH\Catalog\Repository\Sites;

/**
 * One catalog: its settings, the sites in it, and its filters.
 */
class CatalogPage extends AdminPage
{
    const SLUG = 'hfh-catalog-edit';

    const ACTION_UPDATE = 'hfh_catalog_update';
    const ACTION_ADD_SITE = 'hfh_catalog_add_site';
    const ACTION_REMOVE_SITE = 'hfh_catalog_remove_site';
    const ACTION_MOVE_SITE = 'hfh_catalog_move_site';
    const ACTION_ADD_FILTER = 'hfh_catalog_add_filter';
    const ACTION_DELETE_FILTER = 'hfh_catalog_delete_filter';
    const ACTION_MOVE_FILTER = 'hfh_catalog_move_filter';

    const NOTICE_UPDATED = 'hfh_catalog_updated';
    const NOTICE_SITE_ADDED = 'hfh_site_added';
    const NOTICE_SITE_REMOVED = 'hfh_site_removed';
    const NOTICE_SITE_UPDATED = 'hfh_site_updated';
    const NOTICE_FILTER_ADDED = 'hfh_filter_added';
    const NOTICE_FILTER_DELETED = 'hfh_filter_deleted';

    const NOTICES = [
        self::NOTICE_UPDATED,
        self::NOTICE_SITE_ADDED,
        self::NOTICE_SITE_REMOVED,
        self::NOTICE_SITE_UPDATED,
        self::NOTICE_FILTER_ADDED,
        self::NOTICE_FILTER_DELETED,
    ];

    const ERROR_INVALID = 'invalid';
    const ERROR_SLUG_EXISTS = 'slug_exists';
    const ERROR_INVALID_SITE = 'invalid_site';
    const ERROR_INVALID_FILTER = 'invalid_filter';
    const ERROR_FILTER_EXISTS = 'filter_exists';

    public function page_title(): string
    {
        return __('Edit Catalog', 'hfh-catalog');
    }

    public function menu_title(): string
    {
        return __('Edit Catalog', 'hfh-catalog');
    }

    protected function handlers(): array
    {
        return [
            self::ACTION_UPDATE        => 'update_catalog',
            self::ACTION_ADD_SITE      => 'add_site',
            self::ACTION_REMOVE_SITE   => 'remove_site',
            self::ACTION_MOVE_SITE     => 'move_site',
            self::ACTION_ADD_FILTER    => 'add_filter',
            self::ACTION_DELETE_FILTER => 'delete_filter',
            self::ACTION_MOVE_FILTER   => 'move_filter',
        ];
    }

    public function render(): void
    {
        $catalog = $this->current_catalog();
        $catalog_url = self::url(['id' => $catalog->id]);
        $public_url = Frontend::get_instance()->catalog_url($catalog->slug);

        $filters = Filters::findForCatalog((int) $catalog->id);
        $options_by_filter = FilterOptions::findForCatalog((int) $catalog->id);

        $sites_table = new SitesListTable($catalog, $filters, SiteFilterOptions::findForCatalog((int) $catalog->id));
        $sites_table->prepare_items();

        $filters_table = new FiltersListTable($catalog, $filters, $options_by_filter);
        $filters_table->prepare_items();

        $available_sites = Sites::findAvailable((int) $catalog->id);

        $errors = [
            self::ERROR_INVALID        => __('Please enter a valid catalog name or slug.', 'hfh-catalog'),
            self::ERROR_SLUG_EXISTS    => __('This slug is already in use.', 'hfh-catalog'),
            self::ERROR_INVALID_SITE   => __('Please select a valid site.', 'hfh-catalog'),
            self::ERROR_INVALID_FILTER => __('Please enter a valid filter name.', 'hfh-catalog'),
            self::ERROR_FILTER_EXISTS  => __('A filter with this name already exists.', 'hfh-catalog'),
            self::ERROR_DB             => __('Your changes could not be saved.', 'hfh-catalog'),
        ];
        $successes = [
            self::NOTICE_UPDATED        => __('Catalog updated.', 'hfh-catalog'),
            self::NOTICE_SITE_ADDED     => __('Site added.', 'hfh-catalog'),
            self::NOTICE_SITE_REMOVED   => __('Site removed.', 'hfh-catalog'),
            self::NOTICE_SITE_UPDATED   => __('Site updated.', 'hfh-catalog'),
            self::NOTICE_FILTER_ADDED   => __('Filter added.', 'hfh-catalog'),
            self::NOTICE_FILTER_DELETED => __('Filter deleted.', 'hfh-catalog'),
        ];
?>
        <div class="wrap">
            <h1><?php echo esc_html($catalog->name); ?></h1>
            <?php $this->render_notices($errors, $successes); ?>

            <h2><?php esc_html_e('Settings', 'hfh-catalog'); ?></h2>
            <form method="post" action="<?php echo esc_url($catalog_url); ?>">
                <?php wp_nonce_field(self::ACTION_UPDATE); ?>
                <input type="hidden" name="hfh_action" value="<?php echo esc_attr(self::ACTION_UPDATE); ?>">
                <table class="form-table" role="presentation">
                    <tr class="form-field form-required">
                        <th scope="row"><label for="hfh-catalog-name"><?php esc_html_e('Name', 'hfh-catalog'); ?></label></th>
                        <td><input type="text" id="hfh-catalog-name" name="catalog_name" required value="<?php echo esc_attr($this->values['catalog_name'] ?? $catalog->name); ?>"></td>
                    </tr>
                    <tr class="form-field">
                        <th scope="row"><label for="hfh-catalog-slug"><?php esc_html_e('Slug', 'hfh-catalog'); ?></label></th>
                        <td>
                            <input type="text" id="hfh-catalog-slug" name="catalog_slug" aria-describedby="hfh-catalog-slug-desc" value="<?php echo esc_attr($this->values['catalog_slug'] ?? $catalog->slug); ?>">
                            <p class="description" id="hfh-catalog-slug-desc">
                                <?php esc_html_e('Public address:', 'hfh-catalog'); ?>
                                <a href="<?php echo esc_url($public_url); ?>"><?php echo esc_html($public_url); ?></a><br>
                                <?php esc_html_e('Changing the slug changes this address, and existing links to it will stop working.', 'hfh-catalog'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr class="form-field">
                        <th scope="row"><label for="hfh_catalog_description"><?php esc_html_e('Description', 'hfh-catalog'); ?></label></th>
                        <td>
                            <?php
                            wp_editor(
                                $this->values['catalog_description'] ?? $catalog->description ?? '',
                                'hfh_catalog_description',
                                [
                                    'textarea_rows' => 8,
                                    'media_buttons' => false,
                                    'teeny' => true,
                                ]
                            );
                            ?>
                            <p class="description"><?php esc_html_e('Shown under the title on the catalog page.', 'hfh-catalog'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Update', 'hfh-catalog')); ?>
            </form>

            <h2><?php esc_html_e('Sites', 'hfh-catalog'); ?></h2>
            <div class="tablenav top">
                <form method="post" action="<?php echo esc_url($catalog_url); ?>" class="alignleft actions">
                    <?php wp_nonce_field(self::ACTION_ADD_SITE); ?>
                    <input type="hidden" name="hfh_action" value="<?php echo esc_attr(self::ACTION_ADD_SITE); ?>">
                    <label for="hfh-catalog-site" class="screen-reader-text"><?php esc_html_e('Site to add', 'hfh-catalog'); ?></label>
                    <input type="text" id="hfh-catalog-site" name="site" list="hfh-catalog-sites" class="regular-text" required
                        autocomplete="off" placeholder="<?php esc_attr_e('Start typing a site name or URL…', 'hfh-catalog'); ?>">
                    <datalist id="hfh-catalog-sites">
                        <?php foreach ($available_sites as $site) : ?>
                            <option value="<?php echo esc_attr($this->site_label($site)); ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                    <?php submit_button(__('Add site', 'hfh-catalog'), '', 'submit', false); ?>
                </form>
                <br class="clear">
            </div>

            <?php if ($sites_table->has_items()) : ?>
                <?php $sites_table->display(); ?>
            <?php else : ?>
                <p><?php esc_html_e('No sites in this catalog yet.', 'hfh-catalog'); ?></p>
            <?php endif; ?>

            <h2><?php esc_html_e('Filters', 'hfh-catalog'); ?></h2>
            <div class="tablenav top">
                <form method="post" action="<?php echo esc_url($catalog_url); ?>" class="alignleft actions">
                    <?php wp_nonce_field(self::ACTION_ADD_FILTER); ?>
                    <input type="hidden" name="hfh_action" value="<?php echo esc_attr(self::ACTION_ADD_FILTER); ?>">
                    <label for="hfh-filter-name" class="screen-reader-text"><?php esc_html_e('New filter name', 'hfh-catalog'); ?></label>
                    <input type="text" id="hfh-filter-name" name="filter_name" required
                        placeholder="<?php esc_attr_e('New filter name', 'hfh-catalog'); ?>"
                        value="<?php echo esc_attr($this->values['filter_name'] ?? ''); ?>">
                    <?php submit_button(__('Add filter', 'hfh-catalog'), '', 'submit', false); ?>
                </form>
                <br class="clear">
            </div>

            <?php if ($filters_table->has_items()) : ?>
                <?php $filters_table->display(); ?>
            <?php else : ?>
                <p><?php esc_html_e('No filters yet.', 'hfh-catalog'); ?></p>
            <?php endif; ?>
        </div>
<?php
    }

    /* -----------------------------------------------------------------
     * Handlers
     * ----------------------------------------------------------------- */

    protected function update_catalog(): void
    {
        $catalog = $this->current_catalog();

        $this->values['catalog_name'] = sanitize_text_field(wp_unslash($_POST['catalog_name'] ?? ''));
        $this->values['catalog_slug'] = sanitize_title(wp_unslash($_POST['catalog_slug'] ?? ''));
        $this->values['catalog_description'] = wp_kses_post(wp_unslash($_POST['hfh_catalog_description'] ?? ''));

        $name = $this->values['catalog_name'];
        $slug = $this->values['catalog_slug'];
        $slug_generated = $slug === '';
        if ($slug_generated) {
            $slug = sanitize_title($name);
        }

        if ($name === '' || $slug === '') {
            $this->error = self::ERROR_INVALID;
        } elseif (Catalogs::slugExists($slug, (int) $catalog->id)) {
            if ($slug_generated) {
                $slug = Catalogs::uniqueSlug($slug, (int) $catalog->id);
            } else {
                $this->error = self::ERROR_SLUG_EXISTS;
            }
        }

        if ($this->error === '' && !Catalogs::update((int) $catalog->id, $name, $slug, $this->values['catalog_description'])) {
            $this->error = self::ERROR_DB;
        }

        if ($this->error === '') {
            $this->redirect(self::url(['id' => $catalog->id]), self::NOTICE_UPDATED);
        }
    }

    protected function add_site(): void
    {
        $catalog = $this->current_catalog();

        $submitted = wp_unslash($_POST['site'] ?? '');

        $blog_id = 0;
        foreach (Sites::findAvailable((int) $catalog->id) as $site) {
            if ($this->site_label($site) === $submitted) {
                $blog_id = (int) $site->blog_id;
                break;
            }
        }

        if (!$blog_id) {
            $this->error = self::ERROR_INVALID_SITE;
        } elseif (!Sites::add((int) $catalog->id, $blog_id)) {
            $this->error = self::ERROR_DB;
        }

        if ($this->error === '') {
            $this->redirect(self::url(['id' => $catalog->id]), self::NOTICE_SITE_ADDED);
        }
    }

    protected function remove_site(): void
    {
        $catalog = $this->current_catalog();

        if (!Sites::remove((int) $catalog->id, absint($_GET['blog_id'] ?? 0))) {
            $this->error = self::ERROR_DB;
            return;
        }

        $this->redirect(self::url(['id' => $catalog->id]), self::NOTICE_SITE_REMOVED);
    }

    protected function move_site(): void
    {
        $catalog = $this->current_catalog();
        $blog_id = absint($_GET['blog_id'] ?? 0);
        $offset = ($_GET['direction'] ?? '') === 'up' ? -1 : 1;

        if (!Sites::move((int) $catalog->id, $blog_id, $offset)) {
            $this->error = self::ERROR_DB;
            return;
        }

        $this->redirect(self::url(['id' => $catalog->id]) . '#' . SortableListTable::row_element_id(Sites::SORTABLE_TYPE, $blog_id));
    }

    protected function add_filter(): void
    {
        $catalog = $this->current_catalog();

        $this->values['filter_name'] = sanitize_text_field(wp_unslash($_POST['filter_name'] ?? ''));
        $name = $this->values['filter_name'];
        $slug = sanitize_title($name);

        if ($name === '' || $slug === '') {
            $this->error = self::ERROR_INVALID_FILTER;
        } elseif (Filters::slugExists((int) $catalog->id, $slug)) {
            $this->error = self::ERROR_FILTER_EXISTS;
        } elseif (!Filters::create((int) $catalog->id, $name, $slug)) {
            $this->error = self::ERROR_DB;
        }

        if ($this->error === '') {
            $this->redirect(self::url(['id' => $catalog->id]), self::NOTICE_FILTER_ADDED);
        }
    }

    protected function delete_filter(): void
    {
        $catalog = $this->current_catalog();

        if (!Filters::delete((int) $catalog->id, absint($_GET['filter_id'] ?? 0))) {
            $this->error = self::ERROR_DB;
            return;
        }

        $this->redirect(self::url(['id' => $catalog->id]), self::NOTICE_FILTER_DELETED);
    }

    protected function move_filter(): void
    {
        $catalog = $this->current_catalog();
        $filter_id = absint($_GET['filter_id'] ?? 0);
        $offset = ($_GET['direction'] ?? '') === 'up' ? -1 : 1;

        if (!Filters::move((int) $catalog->id, $filter_id, $offset)) {
            $this->error = self::ERROR_DB;
            return;
        }

        $this->redirect(self::url(['id' => $catalog->id]) . '#' . SortableListTable::row_element_id(Filters::SORTABLE_TYPE, $filter_id));
    }

    /**
     * The catalog from the URL, or a 404.
     */
    protected function current_catalog(): object
    {
        $catalog = Catalogs::findById(absint($_GET['id'] ?? 0));
        if (!$catalog) {
            $this->not_found(__('Catalog not found.', 'hfh-catalog'));
        }
        return $catalog;
    }
}
