<?php

namespace LighterLMS\Core;

use LighterLMS\Topics;
use LighterLMS\Lessons;

class Activation {

	public static function register_hooks() {
		register_activation_hook( LIGHTER_LMS__FILE__, array( __CLASS__, 'on_activation' ) );
		register_deactivation_hook( LIGHTER_LMS__FILE__, array( __CLASS__, 'on_deactivation' ) );
	}

	public static function on_activation() {
		global $wpdb;

		$topics  = new Topics();
		$lessons = new Lessons();

		$topics->install();
		$lessons->install();

		flush_rewrite_rules();
	}

	public static function on_deactivation() {
		global $wpdb;

		$wpdb->query( "ALTER TABLE {$wpdb->posts} DROP INDEX post_title_fulltext" );
	}
}
