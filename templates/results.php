<?php

use HfH\Catalog\Frontend;
use HfH\Catalog\Plugin;

/**
 * Rendered by Frontend::render_results(), which passes:
 *
 * @var array  $sites     Sites with their book data, in catalog order.
 * @var array  $selection The active filter selection.
 * @var string $view      Frontend::VIEW_GRID or Frontend::VIEW_LIST.
 */
?>
<?php if ($sites) : ?>
    <ul class="hfh-catalog__list<?php if ($view === Frontend::VIEW_LIST) : ?> hfh-catalog__list--list<?php endif; ?>">
        <?php foreach ($sites as $book) : ?>
            <li>
                <a class="hfh-catalog__card" href="<?php echo esc_url($book->url); ?>">
                    <?php if ($book->cover) : ?>
                        <div class="hfh-catalog__image">
                            <img src="<?php echo esc_url($book->cover); ?>" alt="" loading="lazy">
                        </div>
                    <?php endif; ?>
                    <div class="hfh-catalog__card-text">
                        <?php if ($book->authors) : ?>
                            <div class="hfh-catalog__pretitle"><?php echo esc_html($book->authors); ?></div>
                        <?php endif; ?>
                        <div class="hfh-catalog__title"><?php echo esc_html($book->title); ?></div>
                    </div>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php elseif ($selection) : ?>
    <p><?php esc_html_e('No books match your selection.', 'hfh-catalog'); ?></p>
<?php else : ?>
    <p><?php esc_html_e('This catalog has no books yet.', 'hfh-catalog'); ?></p>
<?php endif; ?>