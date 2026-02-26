<?php

namespace LighterLMS;

defined( 'ABSPATH' ) || exit;

class Autoloader {

	private static string $prefix   = 'LighterLMS\\';
	private static string $base_dir = LIGHTER_LMS_PATH . 'includes/';

	/**
	 * Registers the autoloader with SPL.
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, '_autoload' ) );
	}

	/**
	 * Handles class autoloading logic
	 *
	 * @param   string  $class  The fully-qualified class name.
	 */
	private static function _autoload( string $class ): void {
		// check if class uses the namespace prefix?
		$len = strlen( self::$prefix );
		if ( strncmp( self::$prefix, $class, $len ) !== 0 ) {
			return;
		}

		// grab relative class name.
		$rel_class = substr( $class, $len );

		// replace namespace prefix with the base dir. replace namespace seperators
		// with dir seperators in the $rel_class class name, append .php
		$file = self::$base_dir . strtolower( str_replace( '_', '-', str_replace( '\\', DIRECTORY_SEPARATOR, $rel_class ) ) ) . '.php';

		if ( file_exists( $file ) ) {
			require $file;
		}
	}
}
