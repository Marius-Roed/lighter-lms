<?php

namespace LighterLMS\API;

use LighterLMS\Attributes\Action;
use LighterLMS\Traits\Lighter_LMS_Hooks;

defined( 'ABSPATH' ) || exit;

class REST_API {
	use Lighter_LMS_Hooks;

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
			new Lesson(),
		);

		$this->register_hooks();

		add_action( 'rest_insert_lighter_lessons', array( $this->_controllers[2], 'pre_save_lesson' ), 10, 3 );

		add_filter( 'rest_prepare_lighter_courses', array( $this->_controllers[0], 'prepare_course_item' ), 10, 3 );
		add_filter( 'rest_prepare_lighter_lessons', array( $this->_controllers[2], 'prepare_lesson_item' ), 10, 3 );
	}

	#[Action( 'rest_api_init' )]
	public function register_routes(): void {
		foreach ( $this->_controllers as $controller ) {
			$controller->register_routes();
		}
	}
}
