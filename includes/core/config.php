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
 * @property string $standard_template
 * @property string $course_slug
 */
class Config
{

	private static $_instance = null;

	private $_settings = [];

	private $_builders = [
		"Beaver Builder Plugin",
		"Beaver Builder Plugin (Lite Version)",
		"Brizy",
		"Breakdance",
		"Classic Editor",
		"Cornerstone Page Builder",
		"Divi Builder",
		"Elementor",
		"Elementor Pro",
		"Gutenberg",
		"GeneratePress",
		"Fusion Builder",
		"Flatsome UX Builder",
		"KingComposer",
		"Live Composer",
		"Layers WP",
		"MotoPress Content Editor",
		"Oxygen Builder",
		"SiteOrigin Page Builder",
		"Spectra",
		"Thrive Architect",
		"Visual Composer Website Builder",
		"WPBakery Page Builder",
		"Yellow Pencil",
	];

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
			'course_slug' => 'kurser', // TODO: Make editable
			'standard_template' => LIGHTER_LMS_PATH . '/includes/templates/courses/standard.php',
			'admin_page_path' => 'admin.php?page=' . $admin_url,
			'admin_url' => $admin_url,
			'development' => $this->detect_dev(),
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

	/**
	 * Determine whether the active theme is `$theme`.
	 * Will return the theme name if `$theme` is not specified.
	 *
	 * @param string $theme The theme name to compare to.
	 *
	 * @return bool|string
	 */
	public function is_theme($theme = "")
	{
		$current_theme_name = '';
		$current_theme = wp_get_theme();

		if ($current_theme->exists() && $current_theme->parent()) {
			$parent_theme = $current_theme->parent();

			if ($parent_theme->exists()) {
				$current_theme_name = $parent_theme->get_stylesheet();
			}
		} elseif ($current_theme->exists()) {
			$current_theme_name = $current_theme->get_stylesheet();
		}

		if (!$theme || empty($theme)) {
			return $current_theme_name;
		}

		return $theme === $current_theme_name;
	}

	private function detect_dev()
	{
		if ($_SERVER['REMOTE_ADDR'] == '127.0.0.1' || strpos($_SERVER['SERVER_NAME'], '.local')) {
			return true;
		}

		return false;
	}

	public function get_builders()
	{
		if (!current_user_can('manage_options')) return [];

		$plugins = get_option('active_plugins');
		$builders = [];

		foreach ($plugins as $plugin) {
			$plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
			$name = $plugin_data['Name'];

			if (in_array($name, $this->_builders)) {
				$builders[] = $name;
			}
		}

		return $builders;
	}
}
