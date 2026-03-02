<?php

/**
* Plugin Name: Lighter
* Description: This mu-plugin handles core accesibility by all lighter plugins.
* version: 1.0.0
*/

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/lighter/class-lighter.php';

define( 'LIGHTER_VERSION', '1.0.0' );

function lighter(): Lighter {
	return Lighter::get_instance();
}
