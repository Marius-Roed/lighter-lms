<?php

namespace LighterLMS;

class Lesson_Post extends Post_Type
{
	public function __construct()
	{
		parent::__construct(lighter_lms()->lesson_post_type);
	}

	/**
	 * Register lesson post type.
	 */
	public function register()
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
			'publicly_queryable' => false,
			'show_ui' => true,
			'show_in_rest' => true,
			'show_in_menu' => lighter_lms()->admin_url,
			'query_var' => true,
			'rewrite' => ['slug' => 'lektioner'],
			'menu_icon' => 'dashicons-list-view',
			'capability_type' => 'post',
			'has_archive' => false,
			'hierarchical' => false,
			'menu_position' => null,
			'supports' => ['editor', 'custom_fields'],
			'exclude_from_search' => true,
			'register_meta_box_cb' => [$this, 'register_meta_boxes'],
		];

		register_post_type($this->post_type, $args);

		$this->_handle_third_party_support();
	}

	/**
	 * Handle support for third party plugins.
	 */
	private function _handle_third_party_support()
	{
		if (!did_action('elementor/loaded')) {
			return;
		}

		add_post_type_support($this->post_type, 'elementor');

		register_post_meta($this->post_type, '_elementor_data', [
			'type' => 'string',
			'description' => 'Elementor layout data',
			'single' => true,
			'show_in_rest' => true,
			'schema' => [
				'type' => 'array',
				'context' => ['view', 'edit'],
				'required' => false,
				'arg_options' => [
					'sanitize_callback' => null,
				],
			],
			'auth_callback' => function ($attr, $request, $meta_key) {
				return current_user_can('edit_post', $request['id'] ?? 0);
			},
		]);

		$elementor_meta_keys = [
			'_elementor_page_settings',
			'_elementor_css',
			'_elementor_version',
			'_elementor_edit_mode',
			'_elementor_controls_usage'
		];
		foreach ($elementor_meta_keys as $key) {
			register_post_meta($this->post_type, $key, [
				'type' => 'object',
				'description' => $key . ' for Elementor',
				'single' => true,
				'show_in_rest' => true,
				'schema' => [
					'type' => 'object',
					'context' => ['view', 'edit'],
				],
			]);
		}
	}

	/**
	 * Save lesson content
	 *
	 * @param int		$post_id	The post ID.
	 * @param \WP_Post	$post		The post object.
	 */
	public function save_post($post_id, $post)
	{
		$nonce = $_POST['lighter_nonce'];
		if (! $this->verify_nonce($post, $nonce, $this->post_type . '_fields')) {
			return;
		}

		$settings = $_POST['settings'];

		$this->_save_settings($post, $settings);
	}

	/**
	 * Save lesson settings.
	 *
	 * @param \WP_Post $post The post object.
	 * @param array $args The settings to save.
	 */
	protected function _save_settings($post, $args)
	{
		$topic_db = new Topics();
		$parents = $args['parents'];

		update_post_meta($post->ID, '_lighter_parent_topic', $parents[0] ?? null);
		foreach ($parents as $parent) {
			$topic = $topic_db->get($parent);
			$lesson = new Lessons(['lesson' => $post->ID, 'parent' => $topic->post_id, 'topic' => $topic->ID]);
			$lesson->save();
		}

		if (isset($args['slug']) && !empty($args['slug'])) {
			$slug = sanitize_title($args['slug']);
			if ($slug !== get_post_field('post_name', $post, 'raw')) {
				$this->skip_next_save = true;
				wp_update_post(['ID' => $post->ID, 'post_name' => $slug]);
				$this->skip_next_save = false;
			}
		}

		return;
	}

	/**
	 * Register lesson metaboxes.
	 */
	public function register_meta_boxes()
	{
		add_meta_box(
			'lessonsettingsdiv',
			__('Lesson settings', 'lighterlms'),
			[$this, 'render_settings'],
			$this->post_type,
			'normal',
			'high'
		);
	}

	/**
	 * Lesson settings metabox content
	 *
	 * @param \WP_Post $post The post object.
	 */
	public function render_settings($post)
	{
		lighter_view('lesson-settings', ['admin' => true, 'post' => $post]);
	}
}
