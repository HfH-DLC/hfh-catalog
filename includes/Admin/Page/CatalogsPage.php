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

    const NOTICE_CREATED = 'hfh_catalog_created';
    const NOTICE_DELETED = 'hfh_catalog_deleted';

    const NOTICES = [self::NOTICE_CREATED, self::NOTICE_DELETED];

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
        return [self::ACTION_DELETE => 'delete_catalog'];
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

    protected function delete_catalog(): void
    {
        $catalog = Catalogs::findById(absint($_GET['catalog_id'] ?? 0));
        if (!$catalog) {
            $this->not_found(__('Catalog not found.', Plugin::TEXT_DOMAIN));
        }

        if (!Catalogs::delete((int) $catalog->id)) {
            $this->error = self::ERROR_DB;
            return;
        }

        $this->redirect(self::url(), self::NOTICE_DELETED);
    }
}
