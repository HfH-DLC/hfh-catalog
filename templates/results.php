<?php

use HfH\Catalog\Plugin;

/**
 * Rendered by Frontend::render_results(), which passes:
 *
 * @var array $sites     Sites with their book data, in catalog order.
 * @var array $selection The active filter selection.
 */
?>
<?php if ($sites) : ?>
    <ul class="hfh-catalog__list">
        <?php foreach ($sites as $book) : ?>
            <li class="hfh-catalog__book">
                <a class="hfh-catalog__book-link" href="<?php echo esc_url($book->url); ?>">
                    <?php if ($book->cover) : ?>
                        <img class="hfh-catalog__cover" src="<?php echo esc_url($book->cover); ?>" alt="" loading="lazy">
                    <?php endif; ?>
                    <?php if ($book->authors) : ?>
                        <p class="hfh-catalog__authors"><?php echo esc_html($book->authors); ?></p>
                    <?php endif; ?>
                    <h2 class="hfh-catalog__title"><?php echo esc_html($book->title); ?></h2>
                </a>
                <?php if ($book->excerpt) : ?>
                    <p class="hfh-catalog__excerpt"><?php echo esc_html($book->excerpt); ?></p>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php elseif ($selection) : ?>
    <p><?php esc_html_e('No entries match your selection.', Plugin::TEXT_DOMAIN); ?></p>
<?php else : ?>
    <p><?php esc_html_e('This catalog has no entries yet.', Plugin::TEXT_DOMAIN); ?></p>
<?php endif; ?>