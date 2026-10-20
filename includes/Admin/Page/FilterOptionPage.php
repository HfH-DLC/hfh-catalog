<?php

namespace HfH\Catalog\Admin\Page;

use HfH\Catalog\Repository\FilterOptions;
use HfH\Catalog\Repository\Filters;

/**
 * One filter option. Its slug is regenerated from the name, so only the name is editable.
 */
class FilterOptionPage extends AdminPage
{
    const SLUG = 'hfh-catalog-filter-option';

    const ACTION_UPDATE = 'hfh_filter_option_update';

    const ERROR_INVALID = 'invalid';
    const ERROR_EXISTS = 'exists';

    public function page_title(): string
    {
        return __('Edit Option', 'hfh-catalog');
    }

    public function menu_title(): string
    {
        return __('Edit Option', 'hfh-catalog');
    }

    protected function handlers(): array
    {
        return [self::ACTION_UPDATE => 'update_filter_option'];
    }

    public function render(): void
    {
        $filter_option = $this->current_filter_option();
        $filter = Filters::findById((int) $filter_option->filter_id);
        if (!$filter) {
            $this->not_found(__('Filter not found.', 'hfh-catalog'));
        }

        $errors = [
            self::ERROR_INVALID => __('Please enter a valid option.', 'hfh-catalog'),
            self::ERROR_EXISTS  => __('This option already exists for this filter.', 'hfh-catalog'),
            self::ERROR_DB      => __('Your changes could not be saved.', 'hfh-catalog'),
        ];
?>
        <div class="wrap">
            <h1><?= esc_html($this->page_title()); ?></h1>
            <p>
                <a href="<?= esc_url(FilterPage::url(['id' => $filter->id])); ?>">
                    <?php
                    /* translators: %s: filter name */
                    echo esc_html(sprintf(__('← Back to %s', 'hfh-catalog'), $filter->name));
                    ?>
                </a>
            </p>

            <?php $this->render_notices($errors); ?>

            <form method="post" action="<?= esc_url(self::url(['id' => $filter_option->id])); ?>">
                <?php wp_nonce_field(self::ACTION_UPDATE); ?>
                <input type="hidden" name="hfh_action" value="<?= esc_attr(self::ACTION_UPDATE); ?>">
                <table class="form-table" role="presentation">
                    <tr class="form-field form-required">
                        <th scope="row"><label for="hfh-option-name"><?php esc_html_e('Name', 'hfh-catalog'); ?></label></th>
                        <td><input type="text" id="hfh-option-name" name="filter_option_name" required value="<?= esc_attr($this->values['filter_option_name'] ?? $filter_option->name); ?>"></td>
                    </tr>
                </table>
                <?php submit_button(__('Update', 'hfh-catalog')); ?>
            </form>
        </div>
<?php
    }

    protected function update_filter_option(): void
    {
        $filter_option = $this->current_filter_option();

        $this->values['filter_option_name'] = sanitize_text_field(wp_unslash($_POST['filter_option_name'] ?? ''));
        $name = $this->values['filter_option_name'];
        $slug = $name === $filter_option->name ? $filter_option->slug : sanitize_title($name);

        if ($name === '' || $slug === '') {
            $this->error = self::ERROR_INVALID;
        } elseif (FilterOptions::slugExists((int) $filter_option->filter_id, $slug, (int) $filter_option->id)) {
            $this->error = self::ERROR_EXISTS;
        } elseif (!FilterOptions::update((int) $filter_option->id, $name, $slug)) {
            $this->error = self::ERROR_DB;
        }

        if ($this->error === '') {
            $this->redirect_with_notice(
                FilterPage::url(['id' => $filter_option->filter_id]),
                FilterPage::NOTICE_SPOT_FILTER_OPTIONS
            );
        }
    }

    protected function current_filter_option(): object
    {
        $filter_option = FilterOptions::findById(absint($_GET['id'] ?? 0));
        if (!$filter_option) {
            $this->not_found(__('Option not found.', 'hfh-catalog'));
        }
        return $filter_option;
    }
}
