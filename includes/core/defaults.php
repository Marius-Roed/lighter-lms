<?php

namespace LighterLMS\Core;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * @property string $currency
 * @property bool $course_auto_complete
 * @property bool $course_auto_hide
 * @property bool $course_limit_stock
 * @property bool $course_show_lesson_icons
 * @property bool $course_show_progress
 * @property bool $course_hide_theme_header
 * @property bool $course_hide_theme_sidebar
 * @property bool $course_hide_theme_footer
 * @property string $editor
 */
class Defaults
{
	private static $_instance = null;

	private $_settings = [];
	private $_isDirty = false;

	public static function get_instance()
	{
		if (self::$_instance === null) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct()
	{
		$this->_settings = [
			'currency' => 'USD',
			'course_auto_complete' => true,
			'course_auto_hide' => true,
			'course_limit_stock' => false,
			'course_show_lesson_icons' => false,
			'course_show_progress' => true,
			'course_hide_theme_header' => true,
			'course_hide_theme_sidebar' => false,
			'course_hide_theme_footer' => true,
			'editor' => get_option('lighter_lms_default_builder', 'classic-editor')
		];
	}

	public function __get($key)
	{
		if (array_key_exists($key, $this->_settings)) {
			return $this->_settings[$key];
		}

		$config = Config::get_instance();
		$fallback = $config->$key ?? null;
		if ($fallback !== null) {
			_doing_it_wrong(__FUNCTION__, 'Called a key on defaults that did not exist. Falling back to config.', '1.0');
			return $fallback;
		}

		error_log("Warning: Lighter LMS Defaults key ({$key}) does not exist.");
		return null;
	}

	public function all()
	{
		return $this->_settings;
	}

	public function isDirty()
	{
		return $this->_isDirty;
	}

	public function persist()
	{
		if ($this->_isDirty) {
			update_option('lighter_lms_defaults', $this->_settings);
			$this->_isDirty = false;
		}
	}
}
