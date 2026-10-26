<?php

use HfH\Catalog\Admin\Page\CatalogPage;
use HfH\Catalog\Frontend;
use HfH\Catalog\Repository\Catalogs;
use HfH\Catalog\Repository\Filters;
use HfH\Catalog\Repository\Sites;

$frontend = Frontend::get_instance();
$catalog = $frontend->get_catalog();
$catalog_url = $frontend->catalog_url($catalog->slug);
$filters = Filters::findWithOptionsForCatalog((int) $catalog->id);
$selection = $frontend->selection_from_query($filters);
$view = $frontend->view_from_query();
$sites = $frontend->sites_for_selection((int) $catalog->id, $selection);
$blog_ids = wp_list_pluck($selection ? Sites::findForCatalog((int) $catalog->id) : $sites, 'blog_id');
$listed_catalogs = (int) $catalog->listed === 1 ? Catalogs::findListed() : [];
$views = [
    Frontend::VIEW_GRID => __('Grid', 'hfh-catalog'),
    Frontend::VIEW_LIST => __('List', 'hfh-catalog'),
];

get_header();
?>
<main class="hfh-catalog-page">
    <div class="hfh-catalog">
        <div class="hfh-catalog__header">
            <h1><?= esc_html($catalog->name); ?></h1>

            <?php if (count($listed_catalogs) > 1) : ?>
                <details class="hfh-catalog__switcher">
                    <summary class="hfh-catalog__switcher-toggle">
                        <?php esc_html_e('Catalog selection', 'hfh-catalog'); ?>
                    </summary>
                    <nav class="hfh-catalog__switcher-nav" aria-label="<?php esc_attr_e('Catalogs', 'hfh-catalog'); ?>">
                        <ul class="hfh-catalog__switcher-list">
                            <?php foreach ($listed_catalogs as $listed_catalog) : ?>
                                <li>
                                    <a class="hfh-catalog__switcher-link" href="<?= esc_url($frontend->catalog_url($listed_catalog->slug)); ?>" <?php if ((int) $listed_catalog->id === (int) $catalog->id) : ?>aria-current="page" <?php endif; ?>>
                                        <?= esc_html($listed_catalog->name); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </nav>
                </details>
            <?php endif; ?>
        </div>

        <div class="hfh-catalog__description hfh-content">
            <?= wp_kses_post(wpautop($catalog->description)) ?>
        </div>

        <div class="hfh-catalog__layout">
            <?php if ($sites || $filters) : ?>
                <div class="hfh-catalog__before-filters">
                    <a class="hfh-catalog__skip-link" href="#hfh-catalog-books"><?php esc_html_e('Skip to books', 'hfh-catalog'); ?></a>

                    <?php do_action('hfh_catalog_before_filters', $catalog, $blog_ids, $catalog_url); ?>
                </div>

                <div class="hfh-catalog__views" role="group" aria-label="<?php esc_attr_e('View', 'hfh-catalog'); ?>">
                    <?php foreach ($views as $view_key => $view_label) : ?>
                        <a class="hfh-catalog__view" data-view="<?= esc_attr($view_key); ?>" href="<?= esc_url($frontend->view_url($catalog->slug, $selection, $view_key)); ?>" <?php if ($view_key === $view) : ?>aria-current="true" <?php endif; ?>>
                            <?php if ($view_key === Frontend::VIEW_LIST) : ?>
                                <svg class="hfh-catalog__view-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" focusable="false">
                                    <path stroke-width="2.5" d="M2.75 6h2.5 M2.75 12h2.5 M2.75 18h2.5" />
                                    <path d="M8.25 6H21.25 M8.25 12H21.25 M8.25 18H21.25" />
                                </svg>
                            <?php else : ?>
                                <svg class="hfh-catalog__view-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" focusable="false">
                                    <path d="M3.5 3.5h6.5v6.5H3.5Z M14 3.5h6.5v6.5H14Z M3.5 14h6.5v6.5H3.5Z M14 14h6.5v6.5H14Z" />
                                </svg>
                            <?php endif; ?>
                            <span class="hfh-catalog__visually-hidden"><?= esc_html($view_label); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($filters) : ?>
                <div class="hfh-catalog__sidebar">
                    <form class="hfh-catalog__filters" method="get" action="<?= esc_url($catalog_url); ?>">
                        <div class="hfh-catalog__filters-title"><?php esc_html_e('Filter by', 'hfh-catalog'); ?></div>
                        <div class="hfh-catalog__filters-fieldsets">
                            <?php foreach ($filters as $filter) : ?>
                                <?php if ($filter->options) : ?>
                                    <fieldset class="hfh-catalog__filter">
                                        <legend class="hfh-catalog__filter-legend"><?= esc_html($filter->name); ?></legend>
                                        <div class="hfh-catalog__filter-options">
                                            <?php foreach ($filter->options as $option) :
                                                $id = 'hfh-catalog-option-' . $option->id;
                                                $checked = in_array($option->slug, $selection[$filter->slug] ?? [], true);
                                            ?>
                                                <div class="hfh-catalog__filter-option">
                                                    <input type="checkbox" class="hfh-catalog__checkbox"
                                                        id="<?= esc_attr($id); ?>"
                                                        name="<?= esc_attr($frontend->filter_param($filter->slug)); ?>[]"
                                                        value="<?= esc_attr($option->slug); ?>"
                                                        <?php checked($checked); ?>>
                                                    <label class="hfh-catalog__checkbox-label" for="<?= esc_attr($id); ?>">
                                                        <?= esc_html($option->name); ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </fieldset>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                        <input type="hidden" class="hfh-catalog__view-input"
                            name="<?= esc_attr(Frontend::VIEW_PARAM); ?>"
                            value="<?= esc_attr(Frontend::VIEW_LIST); ?>"
                            <?php disabled($view, Frontend::VIEW_GRID); ?>>

                        <p class="hfh-catalog__filters-actions">
                            <button type="submit"><?php esc_html_e('Apply filters', 'hfh-catalog'); ?></button>
                            <?php if ($selection) : ?>
                                <a href="<?= esc_url($frontend->view_url($catalog->slug, [], $view)); ?>"><?php esc_html_e('Reset', 'hfh-catalog'); ?></a>
                            <?php endif; ?>
                        </p>
                    </form>
                </div>
            <?php endif; ?>

            <div class="hfh-catalog__main">
                <h2 class="hfh-catalog__visually-hidden" id="hfh-catalog-books" tabindex="-1"><?php esc_html_e('Books', 'hfh-catalog'); ?></h2>
                <p class="hfh-catalog__status hfh-catalog__visually-hidden" role="status"><?= esc_html($frontend->results_status($sites, $selection)); ?></p>

                <div class="hfh-catalog__results">
                    <?= $frontend->render_results($sites, $selection, $view); ?>
                </div>
            </div>
        </div>

        <?php if (is_super_admin()) :
            $edit_link_classes = (array) apply_filters('hfh_catalog_edit_link_classes', ['hfh-catalog__edit']);
        ?>
            <a href="<?= esc_url(CatalogPage::url(['id' => $catalog->id])); ?>" class="<?= esc_attr(implode(' ', $edit_link_classes)); ?>">
                <?php esc_html_e('Edit', 'hfh-catalog'); ?>
            </a>
        <?php endif; ?>
    </div>
</main>
<?php
get_footer();
