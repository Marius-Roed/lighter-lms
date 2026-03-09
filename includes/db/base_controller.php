<?php

namespace LighterLMS\DB;

class Base_Controller {

	protected \wpdb $db;

	// public Topics_Controller $topics;
	// public Topic_Lessons_Controller $topic_lessons;
	public Lighter_LMS_Schema $schema;

	public function __construct( \wpdb $wpdb ) {
		$this->schema = new Lighter_LMS_Schema( $wpdb );
	}
}
