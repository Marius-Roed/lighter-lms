<?php

namespace LighterLMS;

abstract class Post_Type
{

	/** @var string */
	protected $post_type;

	/** @var bool */
	protected static $shared_hooks_added = false;

	/** @var bool */
	protected $skip_next_save = false;

	public function __construct($post_type_slug)
	{
		$this->post_type = $post_type_slug;

		if (!post_type_exists($this->post_type)) {
			add_action('init', [$this, 'register']);
		}

		add_action('save_post_' . $this->post_type, [$this, 'save_post'], 10, 2);
		add_action('manage_' . $this->post_type . '_posts_custom_column', [$this, 'custom_columns'], 10, 2);

		add_filter('manage_' . $this->post_type . '_posts_columns', [$this, 'columns']);
		add_filter('rest_' . $this->post_type . '_query', [$this, 'rest_query'], 20, 2);
		add_filter('get_user_option_screen_layout_' . $this->post_type, [$this, 'screen_layout']);

		if (!self::$shared_hooks_added) {
			add_action('do_meta_boxes', [__CLASS__, 'remove_submitdiv']);
			add_action('edit_form_after_title', [__CLASS__, 'no_script']);

			add_filter('post_class', [__CLASS__, 'post_class'], 10, 3);
			add_filter('lighter_lms_admin_object', [__CLASS__, 'js_objects'], 10, 2);
			self::$shared_hooks_added = true;
		}
	}

	/** Registers the post type (override in child class) */
	abstract public function register();

	protected function register_tags($name)
	{
		$name = strpos('-tags', $name) == false ? strtolower($name) . '-tags' : strtolower($name);

		$labels = [
			'name' => _x('Tags', 'taxonomy general name'),
			'singular_name' => _x('Tag', 'taxonomy singular name'),
			'search_items' => __('Search Tags'),
			'popular_items' => __('Popular Tags'),
			'all_items' => __('All Tags'),
			'parent_item' => null,
			'parent_item_colon' => null,
			'edit_item' => __('Edit Tag'),
			'update_item' => __('Update Tag'),
			'add_new_item' => __('Add new Tag'),
			'new_item_name' => __('New Tag name'),
			'separate_items_with_commas' => __('Separate Tags with commas'),
			'add_or_remove_items' => __('Add or remove Tags'),
			'choose_from_most_used' => __('Choose from most used Tags'),
			'menu_name' => __('Tags'),
		];

		register_taxonomy($name, $this->post_type, [
			'hierarchical' => false,
			'labels' => $labels,
			'show_ui' => false,
			'show_in_rest' => true,
			'update_count_callback' => '_update_post_term_count',
			'query_var' => true,
			'rewrite' => ['slug' => $name],
		]);
	}

	/**
	 * Verifies the nonce of the post.
	 *
	 * @param \WP_Post $post The post object.
	 * @param string $nonce The nonce value.
	 * @param string [$action] The action which the nonce was registered with.
	 * 
	 * @return bool
	 */
	public function verify_nonce($post, $nonce, $action = -1)
	{
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return false;
		}

		if ($post->post_type !== $this->post_type) {
			return false;
		}

		if (wp_is_post_revision($post) || !wp_verify_nonce($nonce, $action)) {
			return false;
		}

		if (!current_user_can('edit_post', $post->ID)) {
			return false;
		}

		return !$this->skip_next_save;
	}

	/**
	 * Saves the post data (override in child class)
	 *
	 * @param int		$post_id	The post ID.
	 * @param \WP_Post	$post		The post object.
	 */
	abstract public function save_post($post_id, $post);

	/**
	 * Save the post settings (override in child class)
	 *
	 * @param \WP_Post $post The post object.
	 * @param object $args The settings to save.
	 */
	abstract protected function _save_settings($post, $args);

	/** Registers the post meta boxes (override in child class - default empty) */
	public function register_meta_boxes() {}

	/**
	 * Custom columns for admin list view (override in child class - default none)
	 *
	 * @param string	$column		The column name.
	 * @param int		$post_id	The post ID.
	 */
	public function custom_colums($column, $post_id) {}

	/**
	 * Modifies admin list view columns (override in child class)
	 *
	 * @param string[] $columns The names of columns
	 * @returns string[]
	 */
	public function columns($columns)
	{
		return $columns;
	}

	/**
	 * Modifies REST args (override in child class)
	 *
	 * @param array				$args	The query args.
	 * @param \WP_REST_Request	$req	The request object.
	 * @return array
	 */
	public function rest_query($args, $req)
	{
		return $args;
	}

	/**
	 * Screen layout filter (force single column)
	 *
	 * @param int $columns Current columns layout.
	 * @return int
	 */
	public function screen_layout($columns = 0)
	{
		return 1;
	}

	/** Remove submit and slug metaboxes */
	public static function remove_submitdiv()
	{
		$course_post_type = lighter_lms()->course_post_type;
		$lesson_post_type = lighter_lms()->lesson_post_type;

		remove_meta_box('submitdiv', $course_post_type, 'side');
		remove_meta_box('slugdiv', $course_post_type, 'normal');
		remove_meta_box('submitdiv', $lesson_post_type, 'side');
		remove_meta_box('slugdiv', $lesson_post_type, 'normal');
	}

	/**
	 * Modifies post element classes.
	 *
	 * @param string[] $classes An array of post class names.
	 * @param string[] $class An array of additional class names added to the post.
	 * @param int $post_id The post ID.
	 * @string[]
	 */
	public static function post_class($classes, $class, $post_id)
	{
		if (!is_admin()) {
			return $classes;
		}

		$screen = get_current_screen();

		if ('edit' !== $screen->base && in_array($screen->post_type, lighter_lms()->post_types)) {
			return $classes;
		}

		$classes[] = 'lighter-post';

		return $classes;
	}

	/**
	 * Output JS disabled warning
	 *
	 * @param \WP_Post $post The post object.
	 */
	public static function no_script($post)
	{
		if (!in_array($post->post_type, lighter_lms()->post_types)) {
			return;
		} ?>
		<noscript id="lighter-no-script">
			<div class="content-wrapper">
				<h2>You have JavaScript disabled</h2>
				<p>Some features do not work when JavaScript is disabled. Please enable it to get the best editing experience.</p>
			</div>
		</noscript>
<?php
	}

	/**
	 * Add JS objects to admin
	 *
	 * @param array $obj Existing object
	 * @param string $screen_id The current screen ID.
	 * @return array
	 */
	public static function js_objects($obj, $screen_id)
	{
		if ("lighter_lessons" === $screen_id) {
			$obj['lesson']['settings'] = lighter_get_lesson_settings();
		}

		if ("lighter_courses" === $screen_id) {

			$obj['course']['settings'] = lighter_get_course_settings();
		}

		return $obj;
	}
}
