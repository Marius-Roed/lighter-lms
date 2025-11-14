<?php

namespace LighterLMS;

class Course_Post extends Post_Type
{
	public function __construct()
	{
		parent::__construct(lighter_lms()->course_post_type);

		add_action('admin_post_add_topic', [$this, 'add_topic']);
		add_action('wp_enqueue_scripts', [$this, 'scripts']);
		add_action('template_redirect', [$this, 'course_access']);

		add_action('plugins_loaded', function () {
			$prio = 10;

			if (lighter_lms()->is_theme('breackdance-zero-theme-master')) {
				$prio = 1000010;
			}

			add_filter('template_include', [$this, 'course_template'], $prio);
		});
	}

	/**
	 * Register Course post type
	 */
	public function register()
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
			'menu_icon' => 'dashicons-book-alt',
			'capability_type' => 'post',
			'has_archive' => true,
			'hierarchical' => false,
			'supports' => ['custom-fields'],
			'show_in_menu' => lighter_lms()->admin_url,
			'show_in_rest' => true,
			'rest_controller_class' => 'WP_REST_Posts_Controller',
			'rest_base' => 'lighter_courses',
			'register_meta_box_cb' => [$this, 'register_meta_boxes'],
		];

		register_post_type($this->post_type, $args);

		$this->register_tags('Course');
	}

	/**
	 * Save post content
	 *
	 * @param int		$post_id	The post ID.
	 * @param \WP_Post	$post		The post object.
	 */
	public function save_post($post_id, $post)
	{
		$nonce = $_POST['lighter_nonce'] ?? '';
		if (! $this->verify_nonce($post, $nonce, $this->post_type . '_fields')) {
			return $post_id;
		}

		if (isset($_POST['topics'])) {
			$topic_db = new Topics();
			foreach ($_POST['topics'] as $topic) {
				$data = [
					'post_id' => $post_id,
					'title' => $topic['title'],
					'sort_order' => $topic['sortOrder'],
				];
				$topic_db->update($topic['key'], $data);

				if (isset($topic['lessons'])) {
					foreach ($topic['lessons'] as $lesson) {
						Lesson_Post::save_from_course($lesson, $post_id, $topic, $topic_db);
					}
				}
			}
		}

		$settings = $_POST['settings'] ?? [];

		if (!empty($settings)) {
			$this->_save_settings($post, $settings);
		}
	}

	/**
	 * Save course settings.
	 *
	 * @param \WP_Post	$post The post object.
	 * @param array		$args The settings to save.
	 */
	protected function _save_settings($post, $args)
	{
		$tags = $args['tags'] ?? [];
		$product = $args['product'] ?? [];
		$course_description = $args['description'] ?? '';
		$header = isset($settings['displayHeader']) ? wp_validate_boolean($settings['displayHeader']) : false;
		$footer = isset($settings['displayFooter']) ? wp_validate_boolean($settings['displayFooter']) : false;
		$sidebar = isset($settings['displaySidebar']) ? wp_validate_boolean($settings['displaySidebar']) : false;
		$slug = $args['slug'] ? sanitize_post_field('post_name', $args['slug'], $post->ID, 'raw') : $post->post_name;

		if (!empty($tags)) {
			wp_set_post_terms($post->ID, $tags, 'course-tags');
		}

		if (!empty($product)) {
			$product['slug'] = $slug;
			$product['tags'] = $tags;
			$saved_prod = lighter_save_product($product, $post->ID);

			$img_id = $product['image'][0]['id'] ?? false;
			if ($img_id) set_post_thumbnail($post->ID, $img_id);

			update_post_meta($post->ID, '_lighter_is_restricted', true);
		}

		update_post_meta($post->ID, '_course_description', trim($course_description));
		update_post_meta($post->ID, '_course_display_theme_header', $header);
		update_post_meta($post->ID, '_course_display_theme_sidebar', $sidebar);
		update_post_meta($post->ID, '_course_display_theme_footer', $footer);

		if ($post->post_name !== $slug) {
			$this->skip_next_save = true;
			wp_update_post(['ID' => $post->ID, 'post_name' => $slug]);
			$this->skip_next_save = false;
		}
	}

	/**
	 * Check user access to course.
	 */
	public function course_access()
	{
		if (!is_singular($this->post_type)) return;

		global $post;
		$is_restricted = get_post_meta($post->ID, '_lighter_is_restricted', true);
		$is_restricted = wp_validate_boolean($is_restricted);
		if ($is_restricted) {
			$user_access = new User_Access();
			if (!$user_access->check_course_access($post->ID)) {
				// TODO: Show access denied template.
				wp_die('access denied', 'Please purchase the course or log in to view.', ['response' => 403]);
			}
		}
	}

	/**
	 * Course template loader.
	 *
	 * @param string $template Current template
	 * @return string
	 */
	public function course_template($template)
	{
		if (!is_singular($this->post_type)) {
			return $template;
		}

		$new = null; // TODO: locate_template(lighter_lms()->course_template);

		if (!empty($new)) {
			return $new;
		}

		return lighter_lms()->standard_template;
	}

	/**
	 * Course admin list view columns
	 *
	 * @param array $columns Existing columns.
	 * @return array The newly set columns.
	 */
	public function columns($columns)
	{
		$date = isset($columns['date']) ? $columns['date'] : false;

		if ($date) {
			unset($columns['date']);
		}

		$columns['tags'] = __('Tags');

		if ($date) {
			$columns['date'] = $date;
		}

		return $columns;
	}

	/**
	 * Course custom columns content
	 *
	 * @param string	$column		The column name
	 * @param Int		$post_id	The post ID.
	 */
	public function custom_colums($column, $post_id)
	{
		switch ($column) {
			case 'tags':
				$tags = get_the_term_list($post_id, 'course-tags', '', ', ');
				if ($tags) {
					echo wp_kses_post($tags);
				}
				break;
		}
	}

	/**
	 * Course REST query modifier
	 *
	 * @param array				$args	The query args.
	 * @param \WP_REST_Request	$req	The request object.
	 *
	 * @return array
	 */
	public function rest_query($args, $req)
	{
		$status_param = $req->get_param('filter_status');
		$valid_stati = [];
		if (!empty($status_param) && is_string($status_param)) {
			$statuses = explode(',', $status_param);
			foreach ($statuses as $status) {
				$status = trim($status);
				if (get_post_status_object($status)) {
					$valid_stati[] = $status;
				}
			}
		}

		if (empty($valid_stati)) {
			$valid_stati = ['publish', 'draft', 'future', 'private', 'auto-draft', 'pending'];
		}

		/*
		if (isset($req['status'])) {
			$args['post_status'] = $req->get_param('status');
		}
		*/

		return $args;
	}

	/**
	 * Regitser course metaboxes.
	 */
	public function register_meta_boxes()
	{
		add_meta_box(
			'coursecontentdiv',
			__('Course content', 'lighterlms'),
			[$this, 'details'],
			$this->post_type,
			'normal',
			'high'
		);

		add_meta_box(
			'coursesettingsdiv',
			__('Course settings', 'lighterlms'),
			[$this, 'settings'],
			$this->post_type,
			'normal',
			'high'
		);
	}

	/**
	 * Course details metabox content.
	 *
	 * @param \WP_Post $post The post object.
	 */
	public function details($post)
	{
		lighter_view('course-modules', ['admin' => true, 'post' => $post]);
	}

	/**
	 * Course settings metabox content.
	 *
	 * @param \WP_Post $post The post object.
	 */
	public function settings($post)
	{
		lighter_view('course-settings', ['admin' => true, 'post' => $post]);
	}

	/**
	 * Add a new topic via. form submission.
	 */
	public function add_topic()
	{
		$post_id = (int) $_POST['post_ID'];
		$sort = (int) $_POST['topics_length'];

		$topics_db = new Topics();
		$topics_db->create($post_id, null, 'New topic', $sort + 1);

		wp_redirect(wp_get_referer());
		exit;
	}

	/**
	 * Enqueue needed scripts.
	 */
	public function scripts()
	{
		if (is_singular($this->post_type)) {
			wp_enqueue_style('lighter_lms_frontend');

			wp_enqueue_script('lighter_lms_course_js');
			wp_enqueue_script('wp-api-fetch');
		}
	}
}
