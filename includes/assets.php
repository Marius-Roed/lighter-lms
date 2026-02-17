<?php

namespace LighterLMS;

class Assets {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ) );
	}

	public function register_styles() {
		wp_register_style( 'lighter_lms_frontend', LIGHTER_LMS_URL . 'assets/css/frontend.css', array(), LIGHTER_LMS_VERSION );
	}

	public function register_scripts() {
		wp_register_script( 'lighter_lms_course_js', LIGHTER_LMS_URL . 'assets/js/course.js', array(), LIGHTER_LMS_VERSION, true );
	}
}
