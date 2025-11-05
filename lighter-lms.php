<?php

/**
 * Plugin Name: Lighter LMS
 * Description: Lighter LMS is a lightweight LMS plugin for WordPress.
 * Author: Marius W. Roed
 * Version: 1.0.0-alpha.9
 * Author URI: https://github.com/marius-roed
 * Requires PHP: 8.2
 * Requires at least: 5.3
 * Tested up to: 6.8.3
 * Text Domain: lighterlms
 *
 * @package Lighter
 */

if (! defined('ABSPATH')) {
	exit;
}

define('LIGHTER_LMS__FILE__', __FILE__);
define('LIGHTER_LMS_PATH', plugin_dir_path(LIGHTER_LMS__FILE__));
define('LIGHTER_LMS_URL', plugin_dir_url(LIGHTER_LMS__FILE__));
define('LIGHTER_LMS_VERSION', "1.0.0-alpha.9");

// Autoloader
require_once LIGHTER_LMS_PATH . 'includes/autoloader.php';
\LighterLMS\Autoloader::register();

\LighterLMS\Core\Activation::register_hooks();

\LighterLMS\Core\Lighter_LMS::get_instance();
