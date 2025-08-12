<?php

namespace LighterLMS;

class Post_Types
{

	/**
	 * Course post type
	 *
	 * @var string
	 */
	public $course_post_type;

	/**
	 * Lesson post type
	 *
	 * @var string
	 */
	public $lesson_post_type;

	public function __construct()
	{
		$this->course_post_type = lighter_lms()->course_post_type;
		$this->lesson_post_type = lighter_lms()->lesson_post_type;

		add_action('init', [$this, 'register_course_post_type']);
		add_action('init', [$this, 'register_lesson_post_type']);
	}

	public function register_course_post_type()
	{
		$labels = [
			'name'               => _x('Courses', 'post type plural name', 'lighterlms'),
			'singular_name'      => _x('Course', 'post type singular name', 'lighterlms'),
			'menu_name'          => _x('Courses', 'admin menu', 'lighterlms'),
			'name_admin_bar'     => _x('Course', 'add new on admin bar', 'lighterlms'),
			'add_new'            => _x('Add New', 'add lighterlms course', 'lighterlms'),
			'add_new_item'       => __('Add New Course', 'lighterlms'),
			'new_item'           => __('New Course', 'lighterlms'),
			'edit_item'          => __('Edit Course', 'lighterlms'),
			'view_item'          => __('View Course', 'lighterlms'),
			'all_items'          => __('Courses', 'lighterlms'),
			'search_items'       => __('Search Courses', 'lighterlms'),
			'parent_item_colon'  => __('Parent Courses:', 'lighterlms'),
			'not_found'          => __('No courses found.', 'lighterlms'),
			'not_found_in_trash' => __('No courses found in Trash.', 'lighterlms'),
		];

		$args = [
			'labels' => $labels,
			'description' => __('Course description.', 'lighterlms'),
			'public' => true,
			'publicly_queryable' => true,
			'query_var' => true,
			'rewrite' => [
				'slug' => 'kurser',
				'with_front' => true,
			],
			'menu-icon' => 'dashicons-book-alt',
			'capability_type' => 'post',
			'has_archive' => true,
			'hierarchical' => false,
			'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
			'show_in_menu' => lighter_lms()->admin_url,
			'show_in_rest' => true,
		];

		$course_post_type = $this->course_post_type;
		register_post_type($course_post_type, $args);

		// TODO: Maybe create taxonomies??
	}

	public function register_lesson_post_type()
	{
		$labels = [
			'name'               => _x('Lessons', 'post type plural name', 'lighterlms'),
			'singular_name'      => _x('Lesson', 'post type singular name', 'lighterlms'),
			'menu_name'          => _x('Lessons', 'admin menu', 'lighterlms'),
			'name_admin_bar'     => _x('Lesson', 'add new on admin bar', 'lighterlms'),
			'add_new'            => _x('Add New', 'add lighterlms course', 'lighterlms'),
			'add_new_item'       => __('Add New Lesson', 'lighterlms'),
			'new_item'           => __('New Lesson', 'lighterlms'),
			'edit_item'          => __('Edit Lesson', 'lighterlms'),
			'view_item'          => __('View Lesson', 'lighterlms'),
			'all_items'          => __('Lessons', 'lighterlms'),
			'search_items'       => __('Search Lessons', 'lighterlms'),
			'parent_item_colon'  => __('Parent Lessons:', 'lighterlms'),
			'not_found'          => __('No lessons found.', 'lighterlms'),
			'not_found_in_trash' => __('No lessons found in Trash.', 'lighterlms'),
		];

		$args = [
			'labels' => $labels,
			'description' => __('Lesson description.', 'lighterlms'),
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_rest' => true,
			'show_in_menu' => lighter_lms()->admin_url,
			'qeury_var' => true,
			'rewrite' => ['slug' => 'lektioner'],
			'menu_icon' => 'dashicons-list-view',
			'capability_type' => 'post',
			'has_acrhive' => false,
			'hierarchical' => false,
			'menu_position' => null,
			'supports' => ['title', 'editor'],
			'exclude_from_search' => true,
		];

		register_post_type($this->lesson_post_type, $args);
	}
}
