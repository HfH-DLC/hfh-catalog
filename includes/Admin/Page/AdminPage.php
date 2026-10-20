<?php

namespace HfH\Catalog\Admin\Page;

/**
 * One screen in Network Admin: its markup, its form handlers, its own state.
 *
 * Pages declare their handlers as [hfh_action => method]. The base checks the
 * nonce and the capability, then calls the method.
 */
abstract class AdminPage
{
    const CAPABILITY = 'manage_network_options';

    /** Page slug, i.e. the "page" query parameter. */
    const SLUG = '';

    const NOTICE_QUERY_ARG = 'hfh_notice';

    const ERROR_DB = 'db';

    protected string $action = '';
    protected string $error = '';
    protected array $values = [];

    abstract public function page_title(): string;

    abstract public function menu_title(): string;

    abstract public function render(): void;

    /**
     * @return array [hfh_action => method name]
     */
    protected function handlers(): array
    {
        return [];
    }

    /**
     * @return array [spot => [hfh_action, …]]
     */
    protected function notice_spots(): array
    {
        return [];
    }

    /**
     * Runs on load-{hook}, before anything is rendered.
     */
    public function load(): void
    {
        $action = $_SERVER['REQUEST_METHOD'] === 'POST'
            ? (string) ($_POST['hfh_action'] ?? '')
            : (string) ($_GET['hfh_action'] ?? '');

        $handlers = $this->handlers();
        if ($action === '' || !isset($handlers[$action])) {
            return;
        }

        check_admin_referer($action);
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('Sorry, you are not allowed to execute this action.', 'hfh-catalog'), 403);
        }

        $this->action = $action;
        $this->{$handlers[$action]}();
    }

    /**
     * This page's URL, optionally with extra query parameters.
     */
    public static function url(array $args = []): string
    {
        return add_query_arg(['page' => static::SLUG] + $args, network_admin_url('admin.php'));
    }

    /**
     * A nonced link that triggers one of this page's handlers.
     */
    public static function action_url(string $action, array $args = []): string
    {
        return wp_nonce_url(static::url(['hfh_action' => $action] + $args), $action);
    }

    protected function redirect(string $url, string $anchor = ''): void
    {
        if ($anchor !== '') {
            $url .= '#' . $anchor;
        }
        wp_safe_redirect($url);
        exit;
    }

    protected function redirect_with_notice(string $url, string $anchor = ''): void
    {
        $this->redirect(add_query_arg(self::NOTICE_QUERY_ARG, $this->action, $url), $anchor);
    }

    /**
     * Shows the current error, or the success notice named in the URL.
     */
    protected function render_notices(array $errors, array $successes = [], string $spot = ''): bool
    {
        $notice_action = $this->notice_action();

        if ($this->error !== '') {
            $type = 'error';
            $message = $errors[$this->error] ?? __('Something went wrong.', 'hfh-catalog');
        } else {
            $type = 'success';
            $message = $successes[$notice_action] ?? '';
        }

        if ($message === '' || $this->notice_spot_for_action($notice_action) !== $spot) {
            return false;
        }

        $this->render_notice($type, $message, $spot !== '');
        return true;
    }

    protected function notice_action(): string
    {
        if ($this->error !== '') {
            return $this->action;
        }
        return sanitize_key($_GET[self::NOTICE_QUERY_ARG] ?? '');
    }

    protected function notice_spot_for_action(string $action): string
    {
        foreach ($this->notice_spots() as $spot => $actions) {
            if (in_array($action, $actions, true)) {
                return $spot;
            }
        }
        return '';
    }

    protected function render_notice(string $type, string $message, bool $inline = false): void
    {
        $classes = ['notice', 'notice-' . $type];
        if ($type === 'success') {
            $classes[] = 'is-dismissible';
        }
        if ($inline) {
            $classes[] = 'inline';
        }
?>
        <div class="<?= esc_attr(implode(' ', $classes)); ?>">
            <p><?= esc_html($message); ?></p>
        </div>
<?php
    }

    protected function not_found(string $message): void
    {
        wp_die(esc_html($message), 404);
    }

    /**
     * "Name (path)", used for the site datalist and to resolve it again.
     */
    protected function site_label(\WP_Site $site): string
    {
        return wp_specialchars_decode($site->blogname, ENT_QUOTES) . ' ( ' . rtrim($site->path, '/') . ' )';
    }
}
