<?php

namespace HfH\Catalog\Admin\Page;

use HfH\Catalog\Plugin;
use HfH\Catalog\Repository\Catalogs;
use HfH\Catalog\Repository\FilterOptions;
use HfH\Catalog\Repository\Filters;
use HfH\Catalog\Repository\SiteFilterOptions;
use HfH\Catalog\Repository\Sites;

/**
 * The filter options assigned to one site within one catalog.
 */
class SitePage extends AdminPage
{
    const SLUG = 'hfh-catalog-site';

    const ACTION_UPDATE = 'hfh_site_update';

    public function page_title(): string
    {
        return __('Edit Site', 'hfh-catalog');
    }

    public function menu_title(): string
    {
        return __('Edit Site', 'hfh-catalog');
    }

    protected function handlers(): array
    {
        return [self::ACTION_UPDATE => 'update_site_filter_options'];
    }

    public function render(): void
    {
        [$catalog, $site] = $this->current_catalog_and_site();

        $filters = Filters::findForCatalog((int) $catalog->id);
        $options_by_filter = FilterOptions::findForCatalog((int) $catalog->id);
        $selected_ids = SiteFilterOptions::findIdsForSite((int) $catalog->id, (int) $site->blog_id);

        $errors = [
            self::ERROR_DB => __('Your changes could not be saved.', 'hfh-catalog'),
        ];
?>
        <div class="wrap">
            <h1><?php echo esc_html($site->blogname); ?></h1>
            <p>
                <a href="<?php echo esc_url(CatalogPage::url(['id' => $catalog->id])); ?>">
                    <?php
                    /* translators: %s: catalog name */
                    echo esc_html(sprintf(__('← Back to %s', 'hfh-catalog'), $catalog->name));
                    ?>
                </a>
            </p>

            <?php $this->render_notices($errors); ?>

            <?php if (!$filters) : ?>
                <p><?php esc_html_e('This catalog has no filters yet.', 'hfh-catalog'); ?></p>
            <?php else : ?>
                <form method="post" action="<?php echo esc_url(self::url(['id' => $catalog->id, 'blog_id' => $site->blog_id])); ?>">
                    <?php wp_nonce_field(self::ACTION_UPDATE); ?>
                    <input type="hidden" name="hfh_action" value="<?php echo esc_attr(self::ACTION_UPDATE); ?>">
                    <table class="form-table" role="presentation">
                        <?php foreach ($filters as $filter) :
                            $filter_options = $options_by_filter[$filter->id] ?? [];
                        ?>
                            <tr>
                                <th scope="row"><?php echo esc_html($filter->name); ?></th>
                                <td>
                                    <?php if (!$filter_options) : ?>
                                        <p class="description"><?php esc_html_e('No options yet.', 'hfh-catalog'); ?></p>
                                    <?php else : ?>
                                        <fieldset>
                                            <legend class="screen-reader-text"><span><?php echo esc_html($filter->name); ?></span></legend>
                                            <?php foreach ($filter_options as $filter_option) : ?>
                                                <label>
                                                    <input type="checkbox" name="filter_option_ids[]" value="<?php echo esc_attr($filter_option->id); ?>"
                                                        <?php checked(in_array((int) $filter_option->id, $selected_ids, true)); ?>>
                                                    <?php echo esc_html($filter_option->name); ?>
                                                </label><br>
                                            <?php endforeach; ?>
                                        </fieldset>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                    <?php submit_button(__('Update', 'hfh-catalog')); ?>
                </form>
            <?php endif; ?>
        </div>
<?php
    }

    protected function update_site_filter_options(): void
    {
        [$catalog, $site] = $this->current_catalog_and_site();

        $valid_ids = FilterOptions::findIdsForCatalog((int) $catalog->id);
        $submitted = array_map('absint', (array) ($_POST['filter_option_ids'] ?? []));
        $filter_option_ids = array_values(array_unique(array_intersect($submitted, $valid_ids)));

        if (!SiteFilterOptions::set((int) $catalog->id, (int) $site->blog_id, $filter_option_ids)) {
            $this->error = self::ERROR_DB;
            return;
        }

        $this->redirect(CatalogPage::url(['id' => $catalog->id]), CatalogPage::NOTICE_SITE_UPDATED);
    }

    /**
     * @return array{0: object, 1: \WP_Site}
     */
    protected function current_catalog_and_site(): array
    {
        $catalog = Catalogs::findById(absint($_GET['id'] ?? 0));
        $blog_id = absint($_GET['blog_id'] ?? 0);
        $site = Sites::find($blog_id);

        if (!$catalog || !$site || !Sites::isInCatalog((int) $catalog->id, $blog_id)) {
            $this->not_found(__('Site not found in this catalog.', 'hfh-catalog'));
        }

        return [$catalog, $site];
    }
}
