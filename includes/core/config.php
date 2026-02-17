<?php

namespace LighterLMS\Core;

use LighterLMS\Lessons;
use LighterLMS\Third_Party\Builders;
use LighterLMS\Third_Party\Stores;
use LighterLMS\Topics;
use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * @property string $admin_page_path
 * @property string $admin_url
 * @property bool $development
 * @property string $course_post_type
 * @property string $connected_store
 * @property string $course_slug
 * @property string $lesson_post_type
 * @property int $machineId
 * @property string $path
 * @property string $url
 * @property string $version
 * @property string[] $post_types
 * @property string $standard_template
 */
class Config {


	private static $_instance = null;

	private $_settings = array();

	public static function get_instance() {
		if ( null === self::$_instance ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {

		$admin_url        = 'lighter-lms';
		$course_post_type = apply_filters( 'lighter_lms_course_post_type', 'lighter_courses' );
		$lesson_post_type = apply_filters( 'lighter_lms_lesson_post_type', 'lighter_lessons' );

		if ( ! str_starts_with( $course_post_type, 'lighter_' ) ) {
			$course_post_type = 'lighter_' . $course_post_type;
		}
		if ( ! str_starts_with( $lesson_post_type, 'lighter_' ) ) {
			$lesson_post_type = 'lighter_' . $lesson_post_type;
		}

		$this->_settings = array(
			'path'              => LIGHTER_LMS_PATH,
			'url'               => LIGHTER_LMS_URL,
			'version'           => LIGHTER_LMS_VERSION,
			'course_post_type'  => $course_post_type,
			'lesson_post_type'  => $lesson_post_type,
			'machineId'         => apply_filters( 'lighterlms_use_machine_id', 0 ),
			'post_types'        => array( $course_post_type, $lesson_post_type ),
			'course_slug'       => 'kurser', // TODO: Make editable
			'standard_template' => LIGHTER_LMS_PATH . '/includes/templates/courses/standard.php',
			'admin_page_path'   => 'admin.php?page=' . $admin_url,
			'admin_url'         => $admin_url,
			'development'       => $this->detect_dev(),
		);
	}

	public function defaults() {
		return Defaults::get_instance();
	}

	public function __get( $key ) {
		if ( ! array_key_exists( $key, $this->_settings ) ) {
			error_log( "Warning: Lighter LMS config key ({$key}) does not exist!" );
			return null;
		}

		return $this->_settings[ $key ];
	}

	public function all() {
		return $this->_settings;
	}

	/**
	 * Gets all courses.
	 *
	 * Gets all the courses in an associated list of course->topics->lessons.
	 * @param int $limit The number of courses to get. Default is -1, all posts.
	 */
	public function get_courses( $limit = -1 ) {
		$courses = array();
		$args    = array(
			'post_type'      => $this->course_post_type,
			'post_status'    => 'any',
			'posts_per_page' => $limit,
		);

		$query = new WP_Query( $args );

		if ( ! $query->have_posts() ) {
			wp_reset_postdata();
			return $courses;
		}

		$topics_db = new Topics();

		while ( $query->have_posts() ) {
			$query->the_post();
			global $post;

			$topics = $topics_db->get_by_course( $post->ID );
			$topics = array_map(
				function ( $t ) {
					$lessons_db = new Lessons();
					$lessons    = $lessons_db->get_lessons( array( 'topic' => $t->ID ) );
					return array(
						'key'        => $t->topic_key,
						'post_id'    => $t->post_id,
						'title'      => $t->title,
						'sort_order' => $t->sort_order,
						'lessons'    => $lessons,
					);
				},
				$topics
			);

			$courses[] = array(
				'id'     => $post->ID,
				'title'  => $post->post_title,
				'image'  => array(
					'src' => get_post_thumbnail_id( $post->ID ) ? wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), array( 200, 200 ) )[0] : null,
				),
				'topics' => array(
					...$topics,
				),
			);
		}

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
	public function is_theme( $theme = '' ) {
		$current_theme_name = '';
		$current_theme      = wp_get_theme();

		if ( $current_theme->exists() && $current_theme->parent() ) {
			$parent_theme = $current_theme->parent();

			if ( $parent_theme->exists() ) {
				$current_theme_name = $parent_theme->get_stylesheet();
			}
		} elseif ( $current_theme->exists() ) {
			$current_theme_name = $current_theme->get_stylesheet();
		}

		if ( ! $theme || empty( $theme ) ) {
			return $current_theme_name;
		}

		return $theme === $current_theme_name;
	}

	private function detect_dev() {
		if ( $_SERVER['REMOTE_ADDR'] == '127.0.0.1' || strpos( $_SERVER['SERVER_NAME'], '.local' ) ) {
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
	public function get_builders( $property = 'name' ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return array();
		}

		return Builders::get_builders( $property );
	}

	public function get_stores( $property = 'name' ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return array();
		}

		return Stores::get_stores( $property );
	}
}
