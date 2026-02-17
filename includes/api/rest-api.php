<?php

namespace LighterLMS\API;

defined( 'ABSPATH' ) || exit;

class REST_API {

	private static ?self $_instance = null;

	/** @var Base_Controller[] */
	private array $_controllers = array();

	public static function init(): self {
		if ( self::$_instance === null ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	private function __construct() {
		$this->_controllers = array(
			new Course(),
			new Topic(),
		);

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes(): void {
		foreach ( $this->_controllers as $controller ) {
			$controller->register_routes();
		}
	}
}
