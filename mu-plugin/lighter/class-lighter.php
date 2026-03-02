<?php

defined( 'ABSPATH' ) || exit;

class Lighter {
	private static array $plugins      = array();
	private static array $invalid_keys = array();

	public static function register( string $key, object $instance ): void {
		self::$plugins[ $key ] = $instance;
	}

	public static function get_instance(): self {
		return new self();
	}

	public function __get( string $key ): ?object {
		if ( isset( self::$plugins[ $key ] ) ) {
			return self::$plugins[ $key ];
		}

		if ( ! in_array( $key, self::$invalid_keys ) ) {
			self::$invalid_keys[] = $key;
			error_log( "Lighter: Plugin \"$key\" is not registered or installed." );
		}

		return new class() {
			public function __call( string $name, array $arguments ): null {
				return null;
			}

			public function __get( string $name ): null {
				return null;
			}
		};
	}
}
