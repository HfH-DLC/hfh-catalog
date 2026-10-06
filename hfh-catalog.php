<?php
/*
Plugin Name: HfH Catalog
Description: Network-wide catalogs of subsites with filters.
Version: 1.0.1
Network: true
Author: Matthias Nötzli
Copyright: © 2026, HfH, DLC, Matthias Nötzli
License: GPLv2
Text Domain: hfh-catalog
*/

namespace HfH\Catalog;

if (!defined('ABSPATH')) {
    return;
}

define('HFH_CATALOG_PLUGIN_VERSION', '1.0.1');
define('HFH_CATALOG_PLUGIN_FILE', __FILE__);
define('HFH_CATALOG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HFH_CATALOG_PLUGIN_DIR', plugin_dir_path(__FILE__));

/**
 * Maps HfH\Catalog\Foo\Bar to includes/Foo/Bar.php.
 */
spl_autoload_register(function (string $class): void {
    $prefix = __NAMESPACE__ . '\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $path = HFH_CATALOG_PLUGIN_DIR . 'includes/'
        . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';

    if (file_exists($path)) {
        require_once $path;
    }
});

Plugin::get_instance();
