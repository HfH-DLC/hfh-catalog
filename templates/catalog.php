<?php

use HfH\Catalog\Admin\Page\CatalogPage;
use HfH\Catalog\Frontend;
use HfH\Catalog\Plugin;
use HfH\Catalog\Repository\Catalogs;
use HfH\Catalog\Repository\Filters;

$frontend = Frontend::get_instance();
$catalog = $frontend->get_catalog();
$filters = Filters::findWithOptionsForCatalog((int) $catalog->id);
$selection = $frontend->selection_from_query($filters);
$sites = $frontend->sites_for_selection((int) $catalog->id, $selection);
$listed_catalogs = (int) $catalog->listed === 1 ? Catalogs::findListed() : [];

get_header();
?>
<main class="hfh-catalog-page">
    <div class="hfh-catalog">
        <div class="hfh-catalog__header">
            <h1><?php echo esc_html($catalog->name); ?></h1>

            <?php if (count($listed_catalogs) > 1) : ?>
                <details class="hfh-catalog__switcher">
                    <summary class="hfh-catalog__switcher-toggle">
                        <?php esc_html_e('Catalog:', Plugin::TEXT_DOMAIN); ?>
                        <?php echo esc_html($catalog->name); ?>
                    </summary>
                    <nav class="hfh-catalog__switcher-nav" aria-label="<?php esc_attr_e('Catalogs', Plugin::TEXT_DOMAIN); ?>">
                        <ul class="hfh-catalog__switcher-list">
                            <?php foreach ($listed_catalogs as $listed_catalog) : ?>
                                <li>
                                    <a class="hfh-catalog__switcher-link" href="<?php echo esc_url($frontend->catalog_url($listed_catalog->slug)); ?>" <?php if ((int) $listed_catalog->id === (int) $catalog->id) : ?>aria-current="page" <?php endif; ?>>
                                        <?php echo esc_html($listed_catalog->name); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </nav>
                </details>
            <?php endif; ?>
        </div>

        <div class="hfh-catalog__layout">
            <?php if ($sites || $filters) : ?>
                <div class="hfh-catalog__sidebar">
                    <a class="hfh-catalog__skip-link" href="#hfh-catalog-books"><?php esc_html_e('Skip to books', Plugin::TEXT_DOMAIN); ?></a>

                    <?php do_action('hfh_catalog_before_filters', $catalog); ?>

                    <?php if ($filters) : ?>
                        <form class="hfh-catalog__filters" method="get" action="<?php echo esc_url($frontend->catalog_url($catalog->slug)); ?>">
                            <div class="hfh-catalog__filters-title"><?php esc_html_e('Filter by', Plugin::TEXT_DOMAIN); ?></div>

                            <?php foreach ($filters as $filter) : ?>
                                <?php if ($filter->options) : ?>
                                    <fieldset class="hfh-catalog__filter">
                                        <legend class="hfh-catalog__filter-legend"><?php echo esc_html($filter->name); ?></legend>
                                        <div class="hfh-catalog__filter-options">
                                            <?php foreach ($filter->options as $option) :
                                                $id = 'hfh-catalog-option-' . $option->id;
                                                $checked = in_array($option->slug, $selection[$filter->slug] ?? [], true);
                                            ?>
                                                <div class="hfh-catalog__filter-option">
                                                    <input type="checkbox" class="hfh-catalog__checkbox"
                                                        id="<?php echo esc_attr($id); ?>"
                                                        name="<?php echo esc_attr($frontend->filter_param($filter->slug)); ?>[]"
                                                        value="<?php echo esc_attr($option->slug); ?>"
                                                        <?php checked($checked); ?>>
                                                    <label class="hfh-catalog__checkbox-label" for="<?php echo esc_attr($id); ?>">
                                                        <?php echo esc_html($option->name); ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </fieldset>
                                <?php endif; ?>
                            <?php endforeach; ?>

                            <p class="hfh-catalog__filters-actions">
                                <button type="submit"><?php esc_html_e('Apply filters', Plugin::TEXT_DOMAIN); ?></button>
                                <?php if ($selection) : ?>
                                    <a href="<?php echo esc_url($frontend->catalog_url($catalog->slug)); ?>"><?php esc_html_e('Reset', Plugin::TEXT_DOMAIN); ?></a>
                                <?php endif; ?>
                            </p>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="hfh-catalog__main">
                <h2 class="hfh-catalog__visually-hidden" id="hfh-catalog-books" tabindex="-1"><?php esc_html_e('Books', Plugin::TEXT_DOMAIN); ?></h2>
                <p class="hfh-catalog__status hfh-catalog__visually-hidden" role="status"><?php echo esc_html($frontend->results_status($sites, $selection)); ?></p>

                <div class="hfh-catalog__results">
                    <?php echo $frontend->render_results($sites, $selection); ?>
                </div>
            </div>
        </div>

        <?php if (is_super_admin()) : ?>
            <a href="<?php echo esc_url(CatalogPage::url(['id' => $catalog->id])); ?>" class="hfh-catalog__edit">
                <?php esc_html_e('Edit', Plugin::TEXT_DOMAIN); ?>
            </a>
        <?php endif; ?>
    </div>
</main>
<?php
get_footer();
