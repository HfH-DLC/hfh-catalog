<?php

use HfH\Catalog\Frontend;
use HfH\Catalog\Plugin;
use HfH\Catalog\Repository\Filters;

$frontend = Frontend::get_instance();
$catalog = $frontend->get_catalog();
$filters = Filters::findWithOptionsForCatalog((int) $catalog->id);
$selection = $frontend->selection_from_query($filters);
$sites = $frontend->sites_for_selection((int) $catalog->id, $selection);

get_header();
?>
<main class="hfh-catalog-page">
    <div class="hfh-catalog">
        <h1><?php echo esc_html($catalog->name); ?></h1>

        <div class="hfh-catalog__layout">
            <?php if ($sites || $filters) : ?>
                <div class="hfh-catalog__sidebar">
                    <?php do_action('hfh_catalog_before_filters', $catalog); ?>

                    <?php if ($filters) : ?>
                        <form class="hfh-catalog__filters" method="get" action="<?php echo esc_url(Frontend::catalog_url($catalog->slug)); ?>">
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
                                    <a href="<?php echo esc_url(Frontend::catalog_url($catalog->slug)); ?>"><?php esc_html_e('Reset', Plugin::TEXT_DOMAIN); ?></a>
                                <?php endif; ?>
                            </p>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="hfh-catalog__results">
                <?php echo $frontend->render_results($sites, $selection); ?>
            </div>
        </div>
    </div>
</main>
<?php
get_footer();
