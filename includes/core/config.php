<?php

namespace LighterLMS\Core;

if (! defined('ABSPATH')) {
	exit;
}
/**
 * @property string $path
 * @property string $url
 * @property string $version
 * @property string $course_post_type
 * @property string $lesson_post_type
 * @property string[] $post_types
 * @property string $admin_page_path
 * @property string $admin_url
 * @property bool $development
 * @property string $connected_store
 */
class Config
{

	private static $_instance = null;

	private $_settings = [];

	public static function get_instance()
	{
		if (null === self::$_instance) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct()
	{

		$admin_url = 'lighter-lms';
		$course_post_type = apply_filters('lighter_lms_course_post_type', 'lighter_courses');
		$lesson_post_type = apply_filters('lighter_lms_lesson_post_type', 'lighter_lessons');

		if (! str_starts_with($course_post_type, 'lighter_')) {
			$course_post_type = 'lighter_' . $course_post_type;
		}
		if (! str_starts_with($lesson_post_type, 'lighter_')) {
			$lesson_post_type = 'lighter_' . $lesson_post_type;
		}

		$this->_settings = [
			'path' => LIGHTER_LMS_PATH,
			'url' => LIGHTER_LMS_URL,
			'version' => LIGHTER_LMS_VERSION,
			'course_post_type' => $course_post_type,
			'lesson_post_type' => $lesson_post_type,
			'post_types' => [$course_post_type, $lesson_post_type],
			'admin_page_path' => 'admin.php?page=' . $admin_url,
			'admin_url' => $admin_url,
			'development' => false,
			'connected_store' => 'woocommerce'
		];
	}

	public function defaults()
	{
		return Defaults::get_instance();
	}

	public function __get($key)
	{
		if (! array_key_exists($key, $this->_settings)) {
			error_log("Warning: Lighter LMS config key ({$key}) does not exist!");
			return null;
		}

		return $this->_settings[$key];
	}

	public function all()
	{
		return $this->_settings;
	}
}
