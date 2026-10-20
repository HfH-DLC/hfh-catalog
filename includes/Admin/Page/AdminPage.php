<?php

namespace HfH\Catalog\Admin\Page;

use HfH\Catalog\Plugin;

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

    /** Query parameters this page uses for success notices. */
    const NOTICES = [];

    const ERROR_DB = 'db';

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

    protected function redirect(string $url, string $notice = ''): void
    {
        wp_safe_redirect($notice ? add_query_arg($notice, 1, $url) : $url);
        exit;
    }

    /**
     * Shows the current error, or the success notice named in the URL.
     */
    protected function render_notices(array $errors, array $successes = []): void
    {
        if ($this->error !== '') {
            $message = $errors[$this->error] ?? __('Something went wrong.', 'hfh-catalog');
            printf('<div class="notice notice-error"><p>%s</p></div>', esc_html($message));
            return;
        }

        foreach ($successes as $flag => $message) {
            if (isset($_GET[$flag])) {
                printf('<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html($message));
                return;
            }
        }
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
