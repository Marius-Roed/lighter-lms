<?php

namespace LighterLMS\Core;

use LighterLMS\Lessons;
use LighterLMS\Topics;
use WP_Query;

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
		"Beaver Builder Plugin" => [
			"name" => ["Beaver Builder", "Beaver Builder Plugin (Lite Version)"],
			"slug" => "beaver",
			"foreground" => "#00000000",
			"background" => "#FEAF52",
		],
		"Brizy" => [
			"name" => ["Brizy"],
			"slug" => "brizy",
			"foreground" => "#00000000",
			"background" => "#0E0736",
		],
		"Breakdance" => [
			"name" => ["Breakdance"],
			"slug" => "breakdance",
			"foreground" => "#000000",
			"background" => "#FFC514",
		],
		"Classic Editor" => [
			"name" => ["Classic Editor"],
			"slug" => "classic-editor",
			"foreground" => "#25719B",
			"background" => "#F0F0F1",
		],
		"Cornerstone Page Builder" => [
			"name" => ["Cornerstone Page Builder"],
			"slug" => "cornerstone",
			"foreground" => "#00000000",
			"background" => "#00000000",
		],
		"Divi Builder" => [
			"name" => ["Divi Builder"],
			"slug" => "divi",
			"foreground" => "#FFFFFFF",
			"background" => "#00000000",
		],
		"Elementor" => [
			"name" => ["Elementor", "Elementor Pro"],
			"slug" => "elementor",
			"foreground" => "#F0F0F1",
			"background" => "#92003B",
		],
		"Gutenberg" => [
			"name" => ["Gutenberg"],
			"slug" => "gutenberg",
			"foreground" => "#1E1E1E",
			"background" => "#F0F0F1",
		],
		"Fusion Builder" => [
			"name" => ["Fusion Builder"],
			"slug" => "fusion",
			"foreground" => "#FFFFFF",
			"background" => "#50B3C4",
		],
		"Flatsome UX Builder" => [
			"name" => ["Flatsome UX Builder"],
			"slug" => "flatsome",
			"foreground" => "#00000000",
			"background" => "#00000000",
		],
		"KingComposer" => [
			"name" => ["KingComposer"],
			"slug" => "kingcomposer",
			"foreground" => "#00000000",
			"background" => "#00000000",
		],
		"Live Composer" => [
			"name" => ["Live Composer"],
			"slug" => "live-composer",
			"foreground" => "#00000000",
			"background" => "#2EDCE7",
		],
		"Layers WP" => [
			"name" => ["Layers WP"],
			"slug" => "layers",
			"foreground" => "#00000000",
			"background" => "#00000000",
		],
		"MotoPress Content Editor" => [
			"name" => ["MotoPress Content Editor"],
			"slug" => "motopress",
			"foreground" => "#00000000",
			"background" => "#00000000",
		],
		"Oxygen Builder" => [
			"name" => ["Oxygen Builder"],
			"slug" => "oxygen",
			"foreground" => "#FFFFFF",
			"background" => "#000000",
		],
		"SiteOrigin Page Builder" => [
			"name" => ["SiteOrigin Page Builder"],
			"slug" => "siteorigin",
			"foreground" => "#00000000",
			"background" => "#00000000",
		],
		"Spectra" => [
			"name" => ["Spectra"],
			"slug" => "spectra",
			"foreground" => "#F0F0F1",
			"background" => "#5733FF",
		],
		"Thrive Architect" => [
			"name" => ["Thrive Architect"],
			"slug" => "thrive",
			"foreground" => "#00000000",
			"background" => "#00000000",
		],
		"Visual Composer Website Builder" => [
			"name" => ["Visual Composer Website Builder"],
			"slug" => "visual-composer",
			"foreground" => "#00000000",
			"background" => "#00000000",
		],
		"WPBakery Page Builder" => [
			"name" => ["WPBakery Page Builder"],
			"slug" => "wpbakery",
			"foreground" => "#00000000",
			"background" => "#00000000",
		],
		"Yellow Pencil" => [
			"name" => ["Yellow Pencil"],
			"slug" => "yellow",
			"foreground" => "#00000000",
			"background" => "#00000000",
		],
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
	 * Gets all courses.
	 *
	 * Gets all the courses in an associated list of course->topics->lessons.
	 */
	public function get_courses()
	{
		$courses = [];
		$args = [
			'post_type' => $this->course_post_type,
			'post_status' => 'any',
			'posts_per_page' => -1,
		];

		$query = new WP_Query($args);

		if (!$query->have_posts()) {
			wp_reset_postdata();
			return $courses;
		}

		$topics_db = new Topics();

		while ($query->have_posts()) {
			$query->the_post();
			global $post;

			$topics = $topics_db->get_by_course($post->ID);
			$topics = array_map(function ($t) {
				$lessons_db = new Lessons();
				$lessons = $lessons_db->get_lessons(['topic' => $t->ID]);
				return [
					'key' => $t->topic_key,
					'post_id' => $t->post_id,
					'title' => $t->title,
					'sort_order' => $t->sort_order,
					'lessons' => $lessons,
				];
			}, $topics);

			$courses[] = [
				'title' => $post->post_title,
				'topics' => [
					...$topics
				]
			];
		}
		do_action('qm/debug', $courses);

		wp_reset_postdata();
		return $courses;
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

	/**
	 * Get page builders
	 *
	 * Gets all the active page builder plugin on the site. Accessible properties
	 * are "name", "slug", "foreground" and "background". Defaults to "name".
	 * 
	 * @param string $property
	 * @return array
	 */
	public function get_builders($property = "name")
	{
		if (!current_user_can('manage_options')) return [];

		$plugins = get_option('active_plugins');
		$builders = [];

		foreach ($plugins as $plugin) {
			$plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
			$name = $plugin_data['Name'];
			if (isset($this->_builders[$name]) && ! in_array($this->_builders[$name]['slug'], array_column($builders, 'slug'))) {
				$builders[] = $this->_builders[$name];
			}
		}

		$props = [];
		if ($property == "all") {
			return $builders;
		} else if (in_array($property, array_keys($this->_builders['Classic Editor']))) {
			$props = array_column($builders, $property);
		} else {
			$props = array_column($builders, 'name');
		}

		return $props;
	}
}
