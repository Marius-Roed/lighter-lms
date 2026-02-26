<?php

namespace LighterLMS\Core;

defined( 'ABSPATH' ) || exit;

use LighterLMS\Topics;
use LighterLMS\Lessons;

class Activation {

	public static function register_hooks(): void {
		register_activation_hook( LIGHTER_LMS__FILE__, array( __CLASS__, 'on_activation' ) );
		register_deactivation_hook( LIGHTER_LMS__FILE__, array( __CLASS__, 'on_deactivation' ) );
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
}
