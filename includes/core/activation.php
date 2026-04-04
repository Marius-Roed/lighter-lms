<?php

namespace LighterLMS\Core;

defined( 'ABSPATH' ) || exit;

use LighterLMS\DB\Lighter_LMS_Schema;

class Activation {

	public static function register_hooks(): void {
		register_activation_hook( LIGHTER_LMS__FILE__, array( __CLASS__, 'on_activation' ) );
		register_deactivation_hook( LIGHTER_LMS__FILE__, array( __CLASS__, 'on_deactivation' ) );

		self::_maybe_make_mu_plugin();
	}

	public static function on_activation(): void {
		global $wpdb;
		$schema = new Lighter_LMS_Schema( $wpdb );
		$schema->maybe_upgrade();
	}

	public static function on_deactivation(): void {
	}

	private static function _maybe_make_mu_plugin(): void {
		global $wp_filesystem;

		if ( ! self::_init_filesystem() ) {
			error_log( 'LighterLMS: Could not initialize WP Filesystem API' );
			return;
		}

		$mu_file = WPMU_PLUGIN_DIR . '/lighter.php';
		$source       = LIGHTER_LMS_PATH . 'mu-plugin/';

		if ( $wp_filesystem->exists( $mu_file ) && defined( 'LIGHTER_LMS_REQUIRED_MU_VERSION' ) && version_compare( LIGHTER_LMS_REQUIRED_MU_VERSION, '1.0.0', '>=' ) ) {
			return;
		}

		if ( ! $wp_filesystem->is_dir( WPMU_PLUGIN_DIR ) ) {
			$wp_filesystem->mkdir( WPMU_PLUGIN_DIR );
		}

		$wp_filesystem->copy( $source . 'lighter.php', $mu_file, true );
	}

	private static function _init_filesystem(): ?bool {
		global $wp_filesystem;

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		return WP_Filesystem();
	}
}
