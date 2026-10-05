<?php

namespace HfH\Catalog\Admin\Page;

use HfH\Catalog\Plugin;
use HfH\Catalog\Repository\Catalogs;

class NewCatalogPage extends AdminPage
{
    const SLUG = 'hfh-catalog-new';

    const ACTION_CREATE = 'hfh_catalog_create';

    const ERROR_INVALID = 'invalid';
    const ERROR_SLUG_EXISTS = 'slug_exists';

    public function page_title(): string
    {
        return __('Add New Catalog', 'hfh-catalog');
    }

    public function menu_title(): string
    {
        return __('Add New', 'hfh-catalog');
    }

    protected function handlers(): array
    {
        return [self::ACTION_CREATE => 'create_catalog'];
    }

    public function render(): void
    {
        $errors = [
            self::ERROR_INVALID     => __('Please enter a valid name or slug.', 'hfh-catalog'),
            self::ERROR_SLUG_EXISTS => __('This slug is already in use.', 'hfh-catalog'),
            self::ERROR_DB          => __('The catalog could not be saved.', 'hfh-catalog'),
        ];
?>
        <div class="wrap">
            <h1><?php echo esc_html($this->page_title()); ?></h1>
            <?php $this->render_notices($errors); ?>
            <p><span class="required-field-message"><?php esc_html_e('Required fields are marked', 'hfh-catalog'); ?> <span class="required">*</span></span></p>
            <form method="post" action="<?php echo esc_url(self::url()); ?>">
                <?php wp_nonce_field(self::ACTION_CREATE); ?>
                <input type="hidden" name="hfh_action" value="<?php echo esc_attr(self::ACTION_CREATE); ?>">
                <table class="form-table" role="presentation">
                    <tr class="form-field form-required">
                        <th scope="row"><label for="hfh-catalog-name"><?php esc_html_e('Name', 'hfh-catalog'); ?><span class="required">*</span></label></th>
                        <td><input type="text" id="hfh-catalog-name" name="name" class="regular-text" required value="<?php echo esc_attr($this->values['name'] ?? ''); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="hfh-catalog-slug"><?php esc_html_e('Slug', 'hfh-catalog'); ?></label></th>
                        <td>
                            <input type="text" id="hfh-catalog-slug" name="slug" class="regular-text" aria-describedby="hfh-catalog-slug-desc" value="<?php echo esc_attr($this->values['slug'] ?? ''); ?>">
                            <p class="description" id="hfh-catalog-slug-desc"><?php esc_html_e('Leave empty to generate it from the name.', 'hfh-catalog'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Add catalog', 'hfh-catalog')); ?>
            </form>
        </div>
<?php
    }

    protected function create_catalog(): void
    {
        $this->values = [
            'name' => sanitize_text_field(wp_unslash($_POST['name'] ?? '')),
            'slug' => sanitize_title(wp_unslash($_POST['slug'] ?? '')),
        ];
        $name = $this->values['name'];
        $slug = $this->values['slug'];
        $slug_generated = $slug === '';
        if ($slug_generated) {
            $slug = sanitize_title($name);
        }

        if ($name === '' || $slug === '') {
            $this->error = self::ERROR_INVALID;
        } elseif (Catalogs::slugExists($slug)) {
            if ($slug_generated) {
                $slug = Catalogs::uniqueSlug($slug);
            } else {
                $this->error = self::ERROR_SLUG_EXISTS;
            }
        }

        if ($this->error === '' && !Catalogs::create($name, $slug)) {
            $this->error = self::ERROR_DB;
        }

        if ($this->error === '') {
            $this->redirect(CatalogsPage::url(), CatalogsPage::NOTICE_CREATED);
        }
    }
}
