<?php

namespace LighterLMS\Core;

defined( 'ABSPATH' ) || exit;

use LighterLMS\Topics;
use LighterLMS\Lessons;

class Activation {

	public static function register_hooks(): void {
		register_activation_hook( LIGHTER_LMS__FILE__, array( __CLASS__, 'on_activation' ) );
		register_deactivation_hook( LIGHTER_LMS__FILE__, array( __CLASS__, 'on_deactivation' ) );

		self::_maybe_make_mu_plugin();
	}

	public static function on_activation(): void {
		$topics  = new Topics();
		$lessons = new Lessons();

		$topics->install();
		$lessons->install();

		flush_rewrite_rules();
	}

	public static function on_deactivation(): void {
	}

	private static function _maybe_make_mu_plugin(): void {
		global $wp_filesystem;

		if ( ! self::_init_filesystem() ) {
			error_log( 'LighterLMS: Could not initialize WP Filesystem API' );
			return;
		}

		$mu_bootstrap = WPMU_PLUGIN_DIR . '/lighter.php';
		$mu_dir       = WPMU_PLUGIN_DIR . '/lighter/';
		$source       = LIGHTER_LMS_PATH . 'mu-plugin/';

		if ( $wp_filesystem->exists( $mu_bootstrap ) && defined( 'LIGHTER_LMS_REQUIRED_MU_VERSION' ) && version_compare( LIGHTER_LMS_REQUIRED_MU_VERSION, '1.0.0', '>=' ) ) {
			return;
		}

		if ( ! $wp_filesystem->is_dir( WPMU_PLUGIN_DIR ) ) {
			$wp_filesystem->mkdir( WPMU_PLUGIN_DIR );
		}

		self::_copy_directory( $source, $mu_dir );
		$wp_filesystem->copy( $source . 'lighter.php', $mu_bootstrap, true );
	}

	private static function _init_filesystem(): ?bool {
		global $wp_filesystem;

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		return WP_Filesystem();
	}

	private static function _copy_directory( string $source, string $dest ): bool {
		global $wp_filesystem;

		if ( ! $wp_filesystem->is_dir( $source ) ) {
			error_log( "LighterLMS: Source directory \"$source\" does not exist." );
			return false;
		}

		if ( ! $wp_filesystem->is_dir( $dest ) ) {
			$wp_filesystem->mkdir( $dest );
		}

		$files = $wp_filesystem->dirlist( $source );

		if ( empty( $files ) ) {
			return false;
		}

		foreach ( $files as $file => $file_info ) {
			$source_path = trailingslashit( $source ) . $file;
			$dest_path   = trailingslashit( $dest ) . $file;

			if ( 'd' === $file_info['type'] ) {
				if ( ! self::_copy_directory( $source_path, $dest_path ) ) {
					return false;
				}
			} else {
				if ( ! $wp_filesystem->copy( $source_path, $dest_path, true ) ) {
					error_log( "LighterLMS: Failed to copy \"$source_path\" to \"$dest_path\"." );
					return false;
				}
			}
		}

		return true;
	}
}
