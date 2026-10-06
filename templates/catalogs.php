<?php

use HfH\Catalog\Frontend;
use HfH\Catalog\Plugin;
use HfH\Catalog\Repository\Catalogs;

$catalogs = Catalogs::findListed();

get_header();
?>
<main id="main" class="hfh-catalog">
    <h1><?php esc_html_e('Catalogs', 'hfh-catalog'); ?></h1>
    <?php if ($catalogs) : ?>
        <ul class="hfh-catalog__index">
            <?php foreach ($catalogs as $catalog) : ?>
                <li>
                    <a href="<?= esc_url(Frontend::get_instance()->catalog_url($catalog->slug)); ?>">
                        <?= esc_html($catalog->name); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else : ?>
        <p><?php esc_html_e('No catalogs yet.', 'hfh-catalog'); ?></p>
    <?php endif; ?>
</main>
<?php
get_footer();
