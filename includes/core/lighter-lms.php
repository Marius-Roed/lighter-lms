<?php

namespace LighterLMS\Core;

use LighterLMS\Admin\Admin;
use LighterLMS\Post_Types;

class Lighter_LMS
{
	private static $_instance = null;

	/**
	 * Internal post types object
	 *
	 * @var object
	 */
	private $_post_types;

	/**
	 * Course class object
	 *
	 * @var object
	 */
	private $_course;

	/**
	 * Lesson class object
	 *
	 * @var object
	 */
	private $_lesson;

	/**
	 * Admin class object
	 * 
	 * @var object
	 */
	public $admin;

	public static function get_instance()
	{
		if (null == self::$_instance) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct()
	{
		$this->includes();

		do_action('lighter_lms_before_load');

		// $this->_course = new Course();
		// $this->_lesson = new Lesson();
		$this->admin = new Admin();
		$this->_post_types = new Post_Types();
	}

	/**
	 * Inlcude any file not loaded by the autoloader
	 */
	public function includes()
	{
		include LIGHTER_LMS_PATH . 'includes/lighter-lms-functions.php';
	}
}
