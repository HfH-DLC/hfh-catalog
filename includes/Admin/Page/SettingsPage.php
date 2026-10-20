<?php

namespace HfH\Catalog\Admin\Page;

use HfH\Catalog\Frontend;

class SettingsPage extends AdminPage
{
    const SLUG = 'hfh-catalog-settings';

    const ACTION_UPDATE = 'hfh_catalog_settings_update';

    const ERROR_INVALID = 'invalid';

    public function page_title(): string
    {
        return __('Catalog Settings', 'hfh-catalog');
    }

    public function menu_title(): string
    {
        return __('Settings', 'hfh-catalog');
    }

    protected function handlers(): array
    {
        return [
            self::ACTION_UPDATE => 'update_settings',
        ];
    }

    public function render(): void
    {
        $frontend = Frontend::get_instance();

        $errors = [
            self::ERROR_INVALID => __('Please enter a valid URL base.', 'hfh-catalog'),
            self::ERROR_DB      => __('Your changes could not be saved.', 'hfh-catalog'),
        ];
        $successes = [
            self::ACTION_UPDATE => __('Settings saved.', 'hfh-catalog'),
        ];
?>
        <div class="wrap">
            <h1><?= esc_html($this->page_title()); ?></h1>
            <?php $this->render_notices($errors, $successes); ?>

            <form method="post" action="<?= esc_url(self::url()); ?>">
                <?php wp_nonce_field(self::ACTION_UPDATE); ?>
                <input type="hidden" name="hfh_action" value="<?= esc_attr(self::ACTION_UPDATE); ?>">
                <table class="form-table" role="presentation">
                    <tr class="form-field form-required">
                        <th scope="row"><label for="hfh-catalog-url-base"><?php esc_html_e('URL base', 'hfh-catalog'); ?></label></th>
                        <td>
                            <input type="text" id="hfh-catalog-url-base" name="url_base" required aria-describedby="hfh-catalog-url-base-desc" value="<?= esc_attr($this->values['url_base'] ?? $frontend->url_base()); ?>">
                            <p class="description" id="hfh-catalog-url-base-desc">
                                <?php if (Frontend::INDEX_ENABLED) :
                                    esc_html_e('Catalog index:', 'hfh-catalog'); ?>
                                    <a href="<?= esc_url($frontend->index_url()); ?>"><?= esc_html($frontend->index_url()); ?></a><br>
                                <?php endif;
                                esc_html_e('Changing the URL base changes the address of every catalog, and existing links to them will stop working.', 'hfh-catalog'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Save Changes', 'hfh-catalog')); ?>
            </form>
        </div>
<?php
    }

    protected function update_settings(): void
    {
        $this->values['url_base'] = sanitize_title(wp_unslash($_POST['url_base'] ?? ''));
        $base = $this->values['url_base'];

        if ($base === '') {
            $this->error = self::ERROR_INVALID;
        } elseif (!Frontend::get_instance()->update_url_base($base)) {
            $this->error = self::ERROR_DB;
        }

        if ($this->error === '') {
            $this->redirect_with_notice(self::url());
        }
    }
}
