<?php

namespace HfH\Catalog\Admin\Table;

use Override;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * A list table whose rows can be reordered by drag and drop.
 * The markup carries everything the script needs to call the reorder endpoint.
 */
abstract class SortableListTable extends \WP_List_Table
{
    const ROW_ID_PREFIX = 'hfh-row-';

    public static function row_element_id(string $type, int $id): string
    {
        return self::ROW_ID_PREFIX . $type . '-' . $id;
    }

    /**
     * The id sent in the new order for this row.
     */
    abstract protected function row_id(object $item): int;

    /**
     * One of the repositories' SORTABLE_TYPE constants.
     */
    abstract protected function sortable_type(): string;

    /**
     * The catalog or filter the rows belong to; 0 when they have none.
     */
    protected function sortable_parent(): int
    {
        return 0;
    }

    /**
     * Whether this row can be dragged. Rows that can't keep their place.
     */
    protected function is_sortable_row(object $item): bool
    {
        return true;
    }

    #[Override]
    public function display()
    {
?>
        <div class="hfh-sortable" data-type="<?= esc_attr($this->sortable_type()) ?>" data-parent="<?= esc_attr($this->sortable_parent()) ?>">
            <?php parent::display(); ?>
        </div>
    <?php
    }

    #[Override]
    public function single_row($item)
    {
        $classes = $this->row_classes($item);
    ?>
        <tr id="<?= esc_attr(self::row_element_id($this->sortable_type(), $this->row_id($item))) ?>" <?php if ($classes) : ?>class="<?= esc_attr(implode(' ', $classes)) ?>" <?php endif; ?>>
            <?php $this->single_row_columns($item); ?>
        </tr>
    <?php
    }

    private function row_classes(object $item): array
    {
        if (!$this->is_sortable_row($item)) {
            return [];
        }

        $sortable = array_values(array_filter($this->items, fn($row) => $this->is_sortable_row($row)));

        $classes = ['hfh-sortable__item'];
        if ($item === $sortable[0]) {
            $classes[] = 'hfh-sortable__item--first';
        }
        if ($item === $sortable[count($sortable) - 1]) {
            $classes[] = 'hfh-sortable__item--last';
        }
        return $classes;
    }

    /**
     * A row action link.
     *
     * @param string $confirm Question to confirm before following the link; empty for none.
     */
    protected function action_link(string $url, string $label, string $aria_label, string $class = '', string $confirm = ''): string
    {
        ob_start();
    ?>
        <a href="<?= esc_url($url) ?>" <?php if ($class) : ?>class="<?= esc_attr($class) ?>" <?php endif; ?> aria-label="<?= esc_attr($aria_label) ?>" <?php if ($confirm) : ?>onclick="return confirm('<?= esc_js($confirm) ?>');" <?php endif; ?>><?= esc_html($label) ?></a>
<?php
        return trim(ob_get_clean());
    }
}
