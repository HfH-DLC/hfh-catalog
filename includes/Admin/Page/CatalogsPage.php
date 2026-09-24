<?php

namespace HfH\Catalog\Admin\Page;

use HfH\Catalog\Admin\Table\CatalogsListTable;
use HfH\Catalog\Plugin;
use HfH\Catalog\Repository\Catalogs;

/**
 * Lists all catalogs. Top-level menu page.
 */
class CatalogsPage extends AdminPage
{
    const SLUG = 'hfh-catalogs';

    const ACTION_DELETE = 'hfh_catalog_delete';
    const ACTION_TOGGLE_LISTED = 'hfh_catalog_toggle_listed';
    const ACTION_MOVE = 'hfh_catalog_move';

    const NOTICE_CREATED = 'hfh_catalog_created';
    const NOTICE_DELETED = 'hfh_catalog_deleted';
    const NOTICE_LISTED = 'hfh_catalog_listed';
    const NOTICE_MOVED = 'hfh_catalog_moved';

    const NOTICES = [
        self::NOTICE_CREATED,
        self::NOTICE_DELETED,
        self::NOTICE_LISTED,
        self::NOTICE_MOVED,
    ];

    public function page_title(): string
    {
        return __('Catalogs', Plugin::TEXT_DOMAIN);
    }

    public function menu_title(): string
    {
        return __('Catalogs', Plugin::TEXT_DOMAIN);
    }

    protected function handlers(): array
    {
        return [
            self::ACTION_DELETE => 'delete_catalog',
            self::ACTION_TOGGLE_LISTED => 'toggle_listed',
            self::ACTION_MOVE => 'move_catalog',
        ];
    }

    public function render(): void
    {
        $table = new CatalogsListTable();
        $table->prepare_items();

        $errors = [
            self::ERROR_DB => __('Your changes could not be saved.', Plugin::TEXT_DOMAIN),
        ];
        $successes = [
            self::NOTICE_CREATED => __('Catalog created.', Plugin::TEXT_DOMAIN),
            self::NOTICE_DELETED => __('Catalog deleted.', Plugin::TEXT_DOMAIN),
            self::NOTICE_LISTED => __('Catalog index updated.', Plugin::TEXT_DOMAIN),
            self::NOTICE_MOVED => __('Catalog moved.', Plugin::TEXT_DOMAIN),
        ];
?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo esc_html($this->page_title()); ?></h1>
            <a href="<?php echo esc_url(NewCatalogPage::url()); ?>" class="page-title-action"><?php esc_html_e('Add New', Plugin::TEXT_DOMAIN); ?></a>
            <hr class="wp-header-end">
            <?php
            $this->render_notices($errors, $successes);

            if ($table->has_items()) {
                $table->display();
            } else {
                echo '<p>' . esc_html__('No catalogs yet.', Plugin::TEXT_DOMAIN) . '</p>';
            }
            ?>
        </div>
<?php
    }

    protected function toggle_listed(): void
    {
        $catalog = $this->current_catalog();

        if (!Catalogs::setListed((int) $catalog->id, (string) ($_GET['listed'] ?? '') === '1')) {
            $this->error = self::ERROR_DB;
            return;
        }

        $this->redirect(self::url(), self::NOTICE_LISTED);
    }

    protected function move_catalog(): void
    {
        $catalog = $this->current_catalog();
        $offset = ($_GET['direction'] ?? '') === 'up' ? -1 : 1;

        if (!Catalogs::move((int) $catalog->id, $offset)) {
            $this->error = self::ERROR_DB;
            return;
        }

        $this->redirect(self::url(), self::NOTICE_MOVED);
    }

    protected function delete_catalog(): void
    {
        $catalog = $this->current_catalog();

        if (!Catalogs::delete((int) $catalog->id)) {
            $this->error = self::ERROR_DB;
            return;
        }

        $this->redirect(self::url(), self::NOTICE_DELETED);
    }

    /**
     * The catalog a row action refers to, or a 404.
     */
    protected function current_catalog(): object
    {
        $catalog = Catalogs::findById(absint($_GET['catalog_id'] ?? 0));
        if (!$catalog) {
            $this->not_found(__('Catalog not found.', Plugin::TEXT_DOMAIN));
        }
        return $catalog;
    }
}
