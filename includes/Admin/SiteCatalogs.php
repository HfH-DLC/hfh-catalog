<?php

namespace HfH\Catalog\Admin;

use HfH\Catalog\Admin\Table\SiteCatalogsListTable;
use HfH\Catalog\Plugin;

/**
 * Shows a site which catalogs it appears in. Read-only; assignment happens in
 * Network Admin. Pressbooks replaces the dashboard with a page of its own, so
 * this cannot be a dashboard widget.
 */
class SiteCatalogs
{
    const SLUG = 'hfh-catalogs';

    private static ?self $instance = null;

    public static function get_instance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('admin_menu', [$this, 'register']);
    }

    public function register(): void
    {
        // The main site hosts the catalogs, it is never in one.
        if (is_main_site()) {
            return;
        }

        add_submenu_page(
            'index.php',
            __('Catalogs', Plugin::TEXT_DOMAIN),
            __('Catalogs', Plugin::TEXT_DOMAIN),
            'read',
            self::SLUG,
            [$this, 'render']
        );
    }

    public function render(): void
    {
        $table = new SiteCatalogsListTable();
        $table->prepare_items();
?>
        <div class="wrap">
            <h1><?php esc_html_e('Catalogs', Plugin::TEXT_DOMAIN); ?></h1>
            <?php
            if ($table->has_items()) {
                $table->display();
            } else {
                echo '<p>' . esc_html__('This site is not in any catalog yet.', Plugin::TEXT_DOMAIN) . '</p>';
            }
            ?>
        </div>
<?php
    }
}
