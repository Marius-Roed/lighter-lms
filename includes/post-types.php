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

		if (! post_type_exists($this->course_post_type)) {
			add_action('init', [$this, 'register_course_post_type']);
		}
		if (!post_type_exists($this->lesson_post_type)) {
			add_action('init', [$this, 'register_lesson_post_type']);
		}

		add_action('do_meta_boxes', [$this, 'remove_submitdiv']);
		add_action('save_post_' . $this->course_post_type, [$this, 'save_course'], 10, 2);
		add_action('save_post_' . $this->lesson_post_type, [$this, 'save_lesson'], 10, 2);
		add_action('edit_form_after_title', [$this, 'no_script']);
		add_action('admin_post_add_topic', [$this, 'add_topic']);
		add_action('wp_enqueue_scripts', [$this, 'scripts']);
		add_action('manage_' . $this->course_post_type . '_posts_custom_column', [$this, 'course_custom_columns'], 10, 2);

		add_action('template_redirect', [$this, 'course_access']);

		add_filter('get_user_option_screen_layout_' . $this->course_post_type, [$this, 'screen_layout'], 10, 1);
		add_filter('post_class', [$this, 'post_class'], 10, 3);
		add_filter('manage_' . $this->course_post_type . '_posts_columns', [$this, 'course_columns']);
		add_filter('rest_' . $this->course_post_type . '_query', [$this, 'course_rest_query'], 20, 2);
		add_filter('lighter_admin_object', [$this, 'js_objects'], 10, 2);

		// TODO: Add user access check.

		add_action('plugins_loaded', function () {
			$prio = 10;

			// NOTE: High priority due to breakdance override.
			// TODO: Find a better solution.
			if (lighter_lms()->is_theme("breakdance-zero-theme-master")) {
				$prio = 1000010;
			}

			add_filter('template_include', [$this, 'course_template'], $prio);
		}, 20);
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
			'menu_icon' => 'dashicons-book-alt',
			'capability_type' => 'post',
			'has_archive' => true,
			'hierarchical' => false,
			'supports' => ['custom-fields'],
			'show_in_menu' => lighter_lms()->admin_url,
			'show_in_rest' => true,
			'rest_controller_class' => 'WP_REST_Posts_Controller',
			'rest_base' => 'lighter_courses',
			'register_meta_box_cb' => [$this, 'course_mbs'],
		];

		$course_post_type = $this->course_post_type;
		register_post_type($course_post_type, $args);

		$this->register_tags('course', $this->course_post_type);
	}

	public function register_tags($name, $post_type)
	{
		$name = strtolower($name) . '-tags';

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

		register_taxonomy($name, $post_type, [
			'hierarchical' => false,
			'labels' => $labels,
			'show_ui' => false,
			'show_in_rest' => true,
			'update_count_callback' => '_update_post_term_count',
			'query_var' => true,
			'rewrite' => ['slug' => $name . '-tag'],
		]);
	}

	/**
	 * Saves a course
	 *
	 * @param int $post_id		ID of the post being saved
	 * @param \WP_Post $post	The post Object
	 */
	public function save_course($post_id, $post)
	{

		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return $post_id;
		}

		if ($post->post_type !== lighter_lms()->course_post_type) {
			return $post_id;
		}

		if (wp_is_post_revision($post_id)) {
			return $post_id;
		}

		if (!isset($_POST['lighter_nonce'])) {
			return $post_id;
		}

		if (!wp_verify_nonce($_POST['lighter_nonce'], lighter_lms()->course_post_type . '_fields')) {
			return $post_id;
		}

		if (! current_user_can('edit_post', $post_id)) {
			return $post_id;
		}

		$settings = $_POST['settings'] ?? [];

		$tags = $settings['tags'] ?? false;
		if ($tags) {
			wp_set_post_terms($post_id, $tags, 'course-tags');
		}

		$course_description = $settings['description'] ?? '';

		$product = $settings['product'];
		if ($product) {
			$saved_prod = lighter_save_product($product, $post_id);

			$img_id = $product['images'][0]['id'] ?? false;
			if ($img_id) set_post_thumbnail($post, $img_id);

			update_post_meta($post_id, '_lighter_is_restricted', true);
		}

		update_post_meta($post_id, '_course_description', trim($course_description));

		$header = isset($settings['displayHeader']) ? wp_validate_boolean($settings['displayHeader']) : false;
		$sidebar = isset($settings['displaySidebar']) ? wp_validate_boolean($settings['displaySidebar']) : false;
		$footer = isset($settings['displayFooter']) ? wp_validate_boolean($settings['displayFooter']) : false;

		update_post_meta($post_id, '_course_display_theme_header', $header);
		update_post_meta($post_id, '_course_display_theme_sidebar', $sidebar);
		update_post_meta($post_id, '_course_display_theme_footer', $footer);

		if ($post->post_name !== $settings['slug']) {
			// WARN: Maybe not the best way, but not removing the action causes recursion.
			remove_action('save_post_' . $this->course_post_type, [$this, 'save_course']);
			wp_update_post(['ID' => $post_id, 'post_name' => $settings['slug']]);
		}

		if (isset($_POST['topics'])) {
			$topic_db = new Topics();
			foreach ($_POST['topics'] as $topic) {
				$data = [
					'post_id' => $post_id,
					'title' => $topic['title'],
					'sort_order' => $topic['sortOrder']
				];

				$topic_db->update($topic['key'], $data);

				if (isset($topic['lessons'])) {
					foreach ($topic['lessons'] as $lesson) {
						// TODO: Save lesson.

						$insert_args = [
							'post_title' => $lesson['title'],
							'post_status' => $lesson['status'],
							'post_type' => lighter_lms()->lesson_post_type,
							'meta_input' => [
								'_lighter_sort_order' => $lesson['sortOrder'],
								'_lighter_parent_topic' => $topic['key'],
								'_lighter_lesson_key' => $lesson['key']
							],
						];

						if ($lesson['id']) {
							$insert_args['ID'] = $lesson['id'];
						}

						$inserted = wp_insert_post($insert_args);

						if ($inserted) {
							$t = $topic_db->get($topic['key']);
							$l_args = ['lesson' => $inserted, 'parent' => $post_id, 'topic' => $t->ID];
							$less = new Lessons($l_args);
							$less->save();
						}
					}
				}
			}
		}
	}

	public function course_access()
	{
		if (! is_singular($this->course_post_type)) return;

		global $post;
		$is_restricted = get_post_meta($post->ID, '_lighter_is_restricted', true);
		$is_restricted = filter_var($is_restricted, FILTER_VALIDATE_BOOLEAN);
		if ($is_restricted) {
			$user_access = new User_Access();
			if (!$user_access->check_course_access($post->ID)) {
				wp_die('access denied', 'Please purchase the lesson or log in to view.', ['response' => 403]);
			}
		}
	}

	public function course_template($template)
	{
		if (! is_singular($this->course_post_type)) {
			return $template;
		}

		$new = null; //locate_template(lighter_lms()->course_template);

		if (! empty($new)) {
			return $new;
		}

		$course_template = lighter_lms()->standard_template;
		return $course_template;
	}

	public function course_columns($columns)
	{
		$date = isset($columns['date']);

		if ($date) {
			unset($columns['date']);
		}

		$columns['tags'] = __('Tags');

		if ($date) {
			$columns['date'] = __('Date');
		}
		return $columns;
	}

	public function course_custom_columns($column, $post_id)
	{
		switch ($column) {
			case 'tags':
				$tags = get_the_term_list($post_id, 'course-tags', '', ', ');
				if ($tags) {
					echo $tags;
				}
				break;
		}
	}

	public function course_rest_query($args, $req)
	{
		$status_param = $req->get_param('filter_status');
		$valid_stati = [];
		if (! empty($status_param) && is_string($status_param)) {
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

		if (isset($req['status'])) {
			$args['post_status'] = $req->get_param('status');
		}

		return $args;
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
			'register_meta_box_cb' => [$this, 'lesson_mbs'],
		];

		register_post_type($this->lesson_post_type, $args);

		$this->_handle_third_party_support($this->lesson_post_type);
	}

	/**
	 * Saving a lesson
	 *
	 * @param int $post_id
	 * @param \WP_Post $post
	 */
	public function save_lesson($post_id, $post)
	{
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return $post_id;
		}

		if ($post->post_type !== lighter_lms()->lesson_post_type) {
			return $post_id;
		}

		if (wp_is_post_revision($post_id)) {
			return $post_id;
		}

		if (!wp_verify_nonce($_POST['lighter_nonce'], lighter_lms()->lesson_post_type . '_fields')) {
			return $post_id;
		}

		if (! current_user_can('edit_post', $post_id)) {
			return $post_id;
		}
		$topics_db = new Topics();

		$settings = $_POST['settings'];
		$parents = $settings['parents'];

		update_post_meta($post_id, '_lighter_parent_topic', $parents[0] ?? null);

		foreach ($parents as $parent) {
			$topic = $topics_db->get($parent);
			$lesson = new Lessons(['lesson' => $post_id, 'parent' => $topic->post_id, 'topic' => $topic->ID]);
			$lesson->save();
		}

		if (isset($settings['slug']) && ! empty($settings['slug'])) {
			$slug = sanitize_title($settings['slug']);
			if ($slug !== get_post_field('post_name', $post, 'raw')) {
				// WARN: Maybe not the best way, but not removing the action causes recursion.
				remove_action('save_post_' . $this->lesson_post_type, [$this, 'save_lesson']);
				wp_update_post(['ID' => $post_id, 'post_name' => $slug]);
			}
		}

		/* TODO: 
		   update_post_meta($post_id, '_lighter_lesson_key', $key);
		   update_post_meta($post_id, '_lighter_sort_order', $sort_order);
		   update_post_meta($post_id, '_lighter_course_parent', $course_parent);
		*/
	}

	private function _handle_third_party_support($post_type)
	{
		if ($post_type === $this->lesson_post_type) {
			if (!did_action('elementor/loaded')) {
				return;
			}

			add_post_type_support($post_type, 'elementor');

			register_post_meta($post_type, '_elementor_data', [
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
				register_post_meta($post_type, $key, [
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
	}

	public function course_mbs()
	{
		add_meta_box(
			'coursecontentdiv',
			__('Course content', 'lighterlms'),
			[$this, 'details'],
			$this->course_post_type,
			'normal',
			'high'
		);

		add_meta_box(
			'coursesettingsdiv',
			__('Course settings', 'lighterlms'),
			[$this, 'settings'],
			$this->course_post_type,
			'normal',
			'high'
		);
	}

	public function details($post)
	{
		lighter_view('course-modules.php', ['admin' => true, 'post' => $post]);
	}

	public function settings($post)
	{
		lighter_view('course-settings.php', ['admin' => true, 'post' => $post]);
	}

	public function lesson_mbs()
	{
		add_meta_box(
			'lessonsettingsdiv',
			__('Lesson settings', 'lighterlms'),
			[$this, 'lesson_settings'],
			$this->lesson_post_type,
			'normal',
			'high'
		);
	}

	public function lesson_settings($post)
	{
		lighter_view('lesson-settings', ['admin' => true, 'post' => $post]);
	}

	public function screen_layout($columns = 0)
	{
		return 1;
	}

	public function remove_submitdiv()
	{
		remove_meta_box('submitdiv', lighter_lms()->course_post_type, 'side');
		remove_meta_box('slugdiv', lighter_lms()->course_post_type, 'normal');
		remove_meta_box('submitdiv', lighter_lms()->lesson_post_type, 'side');
		remove_meta_box('slugdiv', lighter_lms()->lesson_post_type, 'normal');
	}

	public function post_class($classes, $class, $post_id)
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
	 * @param \WP_Post $post
	 */
	public function no_script($post)
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

	public function scripts()
	{
		if (is_singular($this->course_post_type)) {
			wp_enqueue_style('lighter_lms_frontend');

			wp_enqueue_script('lighter_lms_course_js');
			wp_enqueue_script('wp-api-fetch');
		}
	}

	/**
	 * @param array $obj
	 * @param string $screen_id
	 */
	public function js_objects($obj, $screen_id)
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
