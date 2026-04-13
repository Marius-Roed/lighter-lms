<?php

namespace LighterLMS;

use LighterLMS\Attributes\Action;
use LighterLMS\Traits\Lighter_LMS_Hooks;

defined( 'ABSPATH' ) || exit;

class Assets {
	use Lighter_LMS_Hooks;

	public function __construct() {
		$this->register_hooks();
	}

	#[Action( 'wp_enqueue_scripts' )]
	public function register_styles(): void {
		wp_register_style( 'lighter_lms_frontend', LIGHTER_LMS_URL . 'assets/css/frontend.css', array(), LIGHTER_LMS_VERSION );
	}

	#[Action( 'wp_enqueue_scripts' )]
	public function register_scripts(): void {
		wp_register_script( 'lighter_lms_course_js', LIGHTER_LMS_URL . 'assets/js/course.js', array(), LIGHTER_LMS_VERSION, true );
	}
}
