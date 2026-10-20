<?php

namespace HfH\Catalog\Admin\Page;

use HfH\Catalog\Admin\Table\FilterOptionsListTable;
use HfH\Catalog\Admin\Table\SortableListTable;
use HfH\Catalog\Repository\Catalogs;
use HfH\Catalog\Repository\FilterOptions;
use HfH\Catalog\Repository\Filters;

/**
 * One filter: its name and its filter options.
 */
class FilterPage extends AdminPage
{
    const SLUG = 'hfh-catalog-filter';

    const ACTION_UPDATE = 'hfh_filter_update';
    const ACTION_ADD_OPTION = 'hfh_filter_option_add';
    const ACTION_DELETE_OPTION = 'hfh_filter_option_delete';
    const ACTION_MOVE_OPTION = 'hfh_filter_option_move';

    const NOTICE_SPOT_FILTER_OPTIONS = 'hfh-filter-section-filter-options';

    const ERROR_INVALID = 'invalid';
    const ERROR_FILTER_EXISTS = 'filter_exists';
    const ERROR_INVALID_OPTION = 'invalid_option';
    const ERROR_OPTION_EXISTS = 'option_exists';

    public function page_title(): string
    {
        return __('Edit Filter', 'hfh-catalog');
    }

    public function menu_title(): string
    {
        return __('Edit Filter', 'hfh-catalog');
    }

    protected function handlers(): array
    {
        return [
            self::ACTION_UPDATE        => 'update_filter',
            self::ACTION_ADD_OPTION    => 'add_filter_option',
            self::ACTION_DELETE_OPTION => 'delete_filter_option',
            self::ACTION_MOVE_OPTION   => 'move_filter_option',
        ];
    }

    protected function notice_spots(): array
    {
        return [
            self::NOTICE_SPOT_FILTER_OPTIONS => [
                self::ACTION_ADD_OPTION,
                self::ACTION_DELETE_OPTION,
                FilterOptionPage::ACTION_UPDATE,
            ],
        ];
    }

    public function render(): void
    {
        $filter = $this->current_filter();
        $catalog = Catalogs::findById((int) $filter->catalog_id);
        if (!$catalog) {
            $this->not_found(__('Catalog not found.', 'hfh-catalog'));
        }

        $filter_url = self::url(['id' => $filter->id]);

        $options_table = new FilterOptionsListTable($filter, FilterOptions::findForFilter((int) $filter->id));
        $options_table->prepare_items();

        $errors = [
            self::ERROR_INVALID        => __('Please enter a valid filter name.', 'hfh-catalog'),
            self::ERROR_FILTER_EXISTS  => __('A filter with this name already exists.', 'hfh-catalog'),
            self::ERROR_INVALID_OPTION => __('Please enter a valid option.', 'hfh-catalog'),
            self::ERROR_OPTION_EXISTS  => __('This option already exists for this filter.', 'hfh-catalog'),
            self::ERROR_DB             => __('Your changes could not be saved.', 'hfh-catalog'),
        ];
        $successes = [
            self::ACTION_UPDATE             => __('Filter updated.', 'hfh-catalog'),
            self::ACTION_ADD_OPTION         => __('Option added.', 'hfh-catalog'),
            FilterOptionPage::ACTION_UPDATE => __('Option updated.', 'hfh-catalog'),
            self::ACTION_DELETE_OPTION      => __('Option deleted.', 'hfh-catalog'),
        ];
?>
        <div class="wrap">
            <h1><?= esc_html($filter->name); ?></h1>
            <p>
                <a href="<?= esc_url(CatalogPage::url(['id' => $catalog->id])); ?>">
                    <?php
                    /* translators: %s: catalog name */
                    echo esc_html(sprintf(__('← Back to %s', 'hfh-catalog'), $catalog->name));
                    ?>
                </a>
            </p>

            <?php $this->render_notices($errors, $successes); ?>

            <h2><?php esc_html_e('Settings', 'hfh-catalog'); ?></h2>
            <form method="post" action="<?= esc_url($filter_url); ?>">
                <?php wp_nonce_field(self::ACTION_UPDATE); ?>
                <input type="hidden" name="hfh_action" value="<?= esc_attr(self::ACTION_UPDATE); ?>">
                <table class="form-table" role="presentation">
                    <tr class="form-field form-required">
                        <th scope="row"><label for="hfh-filter-name"><?php esc_html_e('Name', 'hfh-catalog'); ?></label></th>
                        <td><input type="text" id="hfh-filter-name" name="filter_name" required value="<?= esc_attr($this->values['filter_name'] ?? $filter->name); ?>"></td>
                    </tr>
                </table>
                <?php submit_button(__('Update', 'hfh-catalog')); ?>
            </form>

            <h2 id="<?= esc_attr(self::NOTICE_SPOT_FILTER_OPTIONS); ?>" class="hfh-scroll-target"><?php esc_html_e('Options', 'hfh-catalog'); ?></h2>
            <?php $this->render_notices($errors, $successes, self::NOTICE_SPOT_FILTER_OPTIONS); ?>
            <div class="tablenav top">
                <form method="post" action="<?= esc_url($filter_url . '#' . self::NOTICE_SPOT_FILTER_OPTIONS); ?>" class="alignleft actions">
                    <?php wp_nonce_field(self::ACTION_ADD_OPTION); ?>
                    <input type="hidden" name="hfh_action" value="<?= esc_attr(self::ACTION_ADD_OPTION); ?>">
                    <label for="hfh-option-name" class="screen-reader-text"><?php esc_html_e('New option name', 'hfh-catalog'); ?></label>
                    <input type="text" id="hfh-option-name" name="filter_option_name" required
                        placeholder="<?php esc_attr_e('New option name', 'hfh-catalog'); ?>"
                        value="<?= esc_attr($this->values['filter_option_name'] ?? ''); ?>"
                        <?= $this->notice_action() === self::ACTION_ADD_OPTION ? 'autofocus' : ''; ?>>
                    <?php submit_button(__('Add option', 'hfh-catalog'), '', 'submit', false); ?>
                </form>
                <br class="clear">
            </div>
            <?php if ($options_table->has_items()) :
                $options_table->display();
            else : ?>
                <p><?php esc_html_e('No options yet.', 'hfh-catalog'); ?></p>
            <?php endif; ?>
        </div>
<?php
    }

    /* -----------------------------------------------------------------
     * Handlers
     * ----------------------------------------------------------------- */

    protected function update_filter(): void
    {
        $filter = $this->current_filter();

        $this->values['filter_name'] = sanitize_text_field(wp_unslash($_POST['filter_name'] ?? ''));
        $name = $this->values['filter_name'];
        $slug = $name === $filter->name ? $filter->slug : sanitize_title($name);

        if ($name === '' || $slug === '') {
            $this->error = self::ERROR_INVALID;
        } elseif (Filters::slugExists((int) $filter->catalog_id, $slug, (int) $filter->id)) {
            $this->error = self::ERROR_FILTER_EXISTS;
        } elseif (!Filters::update((int) $filter->id, $name, $slug)) {
            $this->error = self::ERROR_DB;
        }

        if ($this->error === '') {
            $this->redirect_with_notice(self::url(['id' => $filter->id]));
        }
    }

    protected function add_filter_option(): void
    {
        $filter = $this->current_filter();

        $this->values['filter_option_name'] = sanitize_text_field(wp_unslash($_POST['filter_option_name'] ?? ''));
        $name = $this->values['filter_option_name'];
        $slug = sanitize_title($name);

        if ($name === '' || $slug === '') {
            $this->error = self::ERROR_INVALID_OPTION;
        } elseif (FilterOptions::slugExists((int) $filter->id, $slug)) {
            $this->error = self::ERROR_OPTION_EXISTS;
        } elseif (!FilterOptions::create((int) $filter->id, $name, $slug)) {
            $this->error = self::ERROR_DB;
        }

        if ($this->error === '') {
            $this->redirect_with_notice(self::url(['id' => $filter->id]), self::NOTICE_SPOT_FILTER_OPTIONS);
        }
    }

    protected function delete_filter_option(): void
    {
        $filter = $this->current_filter();

        if (!FilterOptions::delete((int) $filter->id, absint($_GET['filter_option_id'] ?? 0))) {
            $this->error = self::ERROR_DB;
            return;
        }

        $this->redirect_with_notice(self::url(['id' => $filter->id]), self::NOTICE_SPOT_FILTER_OPTIONS);
    }

    protected function move_filter_option(): void
    {
        $filter = $this->current_filter();
        $filter_option_id = absint($_GET['filter_option_id'] ?? 0);
        $offset = ($_GET['direction'] ?? '') === 'up' ? -1 : 1;

        if (!FilterOptions::move((int) $filter->id, $filter_option_id, $offset)) {
            $this->error = self::ERROR_DB;
            return;
        }

        $this->redirect(self::url(['id' => $filter->id]), SortableListTable::row_element_id(FilterOptions::SORTABLE_TYPE, $filter_option_id));
    }

    protected function current_filter(): object
    {
        $filter = Filters::findById(absint($_GET['id'] ?? 0));
        if (!$filter) {
            $this->not_found(__('Filter not found.', 'hfh-catalog'));
        }
        return $filter;
    }
}
