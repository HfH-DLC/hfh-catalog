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
<main id="main" class="hfh-catalog">
    <h1><?php echo esc_html($catalog->name); ?></h1>

    <?php if ($filters) : ?>
        <form class="hfh-catalog__filters" method="get" action="<?php echo esc_url(Frontend::catalog_url($catalog->slug)); ?>">
            <?php foreach ($filters as $filter) : ?>
                <?php if ($filter->options) : ?>
                    <fieldset class="hfh-catalog__filter">
                        <legend><?php echo esc_html($filter->name); ?></legend>
                        <?php foreach ($filter->options as $option) :
                            $id = 'hfh-option-' . $option->id;
                            $checked = in_array($option->slug, $selection[$filter->slug] ?? [], true);
                        ?>
                            <label for="<?php echo esc_attr($id); ?>">
                                <input type="checkbox" id="<?php echo esc_attr($id); ?>"
                                    name="<?php echo esc_attr($frontend->filter_param($filter->slug)); ?>[]"
                                    value="<?php echo esc_attr($option->slug); ?>"
                                    <?php checked($checked); ?>>
                                <?php echo esc_html($option->name); ?>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>
                <?php endif; ?>
            <?php endforeach; ?>

            <p class="hfh-catalog__actions">
                <button type="submit"><?php esc_html_e('Apply filters', Plugin::TEXT_DOMAIN); ?></button>
                <?php if ($selection) : ?>
                    <a href="<?php echo esc_url(Frontend::catalog_url($catalog->slug)); ?>"><?php esc_html_e('Reset', Plugin::TEXT_DOMAIN); ?></a>
                <?php endif; ?>
            </p>
        </form>
    <?php endif; ?>

    <div class="hfh-catalog__results">
        <?php echo $frontend->render_results($sites, $selection); ?>
    </div>
</main>
<?php
get_footer();
