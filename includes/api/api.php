<?php

namespace LighterLMS\API;

use LighterLMS\Lesson_Content;
use LighterLMS\Topics;
use LighterLMS\User_Access;
use WP_Error;
use WP_Posts_List_Table;
use WP_Query;
use WP_REST_Response;

class API
{
	private $namespace = 'lighterlms/v1';

	public function __construct()
	{
		add_action('rest_api_init', [$this, 'register_lesson_routes']);
		add_action('rest_api_init', [$this, 'register_topic_routes']);
		add_action('rest_api_init', [$this, 'register_course_routes']);

		add_filter('rest_' . lighter_lms()->course_post_type . '_query', [$this, 'course_rest'], 10, 2);
		add_filter('rest_prepare_' . lighter_lms()->lesson_post_type, [$this, 'ensure_fields'], 10, 3);
		add_filter('rest_prepare_' . lighter_lms()->course_post_type, [$this, 'ensure_fields'], 10, 3);
		add_filter('rest_prepare_user', [$this, 'ensure_user'], 10, 3);

		/*
		register_rest_field('user', 'email', [
			'get_callback' => function ($user) {
				$u = new \WP_User($user['id']);
				return $u->user_email;
			},
			'update_callback' => null,
			'schema' => [
				'description' => 'tester',
				'type' => 'string',
				'format' => 'text',
				'context' => ['view', 'edit'],
				'readonly' => true,
			]
		]);
		*/
	}

	public function register_lesson_routes()
	{
		register_rest_route($this->namespace, '/lesson', [
			'methods' => 'POST',
			'callback' => [$this, 'create_lesson'],
			'permission_callback' => fn() => current_user_can('edit_posts'),
		]);

		register_rest_route($this->namespace, '/lesson/(?P<id>\d+)', [
			'methods' => 'GET',
			'callback' => [$this, 'get_lesson'],
			'permission_callback' => '__return_true',
		]);

		register_rest_route($this->namespace, '/lesson/(?P<id>\d+)', [
			'methods' => 'PUT,PATCH',
			'callback' => [$this, 'update_lesson'],
			'permission_callback' => fn() => current_user_can('edit_posts'),
		]);

		register_rest_route($this->namespace, '/lesson/(?P<key>[a-z0-9]+)', [
			'methods' => 'DELETE',
			'callback' => [$this, 'delete_lesson'],
			'permission_callback' => fn() => current_user_can('delete_posts'),
		]);
	}

	/**
	 * Creates a new lesson post
	 *
	 * @param \WP_REST_Request $req The request.
	 * @return \WP_REST_Response | \WP_Error
	 */
	public function create_lesson($req)
	{
		$args = $req->get_json_params();

		if (! isset($args['parent_topic'])) {
			return new \WP_Error('creation_failed', 'Cannot create a Lesson without a parent topic', ['status' => 400]);
		}

		$new_post = [
			'post_type' => lighter_lms()->lesson_post_type,
			'post_title' => $args['title'] ?? 'New lesson',
			'post_status' => $args['status'] ?? 'publish',
		];

		$new_id = wp_insert_post($new_post);

		if (is_wp_error($new_id)) {
			return $new_id;
		}

		if (! empty($args['meta']) && is_array($args['meta'])) {
			foreach ($args['meta'] as $key => $val) {
				update_post_meta($new_id, $key, $val);
			}
		}

		return rest_ensure_response(array_merge(get_post($new_id, ARRAY_A), ['permalink' => admin_url("post.php?post={$new_id}&action=edit")]));
	}

	/**
	 * Fetches a lesson
	 *
	 * @param \WP_REST_Request $req The request.
	 * @return \WP_REST_Response | \WP_Error
	 */
	public function get_lesson($req)
	{
		$id = (int) $req->get_param('id');
		$only_content = $req->get_param('content_only');
		$lesson = get_post($id);

		if (!$lesson) {
			return new \WP_Error('not_found', "Lesson with ID {$id} not found", ['status' => 404]);
		}

		$user_id = wp_get_current_user()->ID;

		$user = new User_Access($user_id);
		$course_id = (int) $req->get_header('course');

		if (!$user->check_lesson_access($id, $course_id)) {
			return new \WP_Error('access_denied', 'You do not have access to this lesson', ['status' => 403]);
		}

		if ($only_content) {
			$supported_builders = [
				'elementor' => 'handle_elementor_content',
				'beaver-builder' => 'handle_beaver_builder_content',
				'divi' => 'handle_divi_content',
				'gutenberg' => 'handle_gutenberg_content',
				'breakdance' => 'handle_breakdance_content',
				'bricks' => 'handle_bricks_content'
			];
			$builder = Lesson_Content::get_builder($id);

			global $wp_query, $post;
			setup_postdata($lesson);

			if ($builder && isset($supported_builders[$builder])) {
				$func = [Lesson_Content::class, $supported_builders[$builder]];
				$content = call_user_func($func, $id, $lesson);
			} else {
				$content = Lesson_Content::get_content($lesson);
			}

			wp_reset_postdata();

			return rest_ensure_response([
				'id' => $lesson->ID,
				'title' => get_the_title($lesson),
				'slug' => $lesson->post_name,
				'content' => $content,
				'builder' => $builder,
				'styles' => Lesson_Content::get_styles($id, $builder),
			]);
		}

		if ($lesson->post_type !== lighter_lms()->lesson_post_type) {
			return new \WP_Error('lighter_lms_forbidden', "Post with ID {$id} is not of post type: " . lighter_lms()->lesson_post_type, ['status' => 403]);
		}

		return rest_ensure_response(['lesson' => $lesson]);
	}

	/**
	 * Updates a lesson.
	 *
	 * @param \WP_REST_Request $req The request.
	 * @return \WP_REST_Response | \WP_Error
	 */
	public function update_lesson($req)
	{
		$id = (int) $req->get_param('id');
		$args = $req->get_json_params();
		$post = get_post($id);

		if (!$post) {
			return new \WP_Error('not_found', "Lesson with ID {$id} not found", ['status' => 404]);
		}

		if ($post->post_type !== lighter_lms()->lesson_post_type) {
			return new \WP_Error('lighter_lms_forbidden', "Post with ID {$id} is not of post type: " . lighter_lms()->lesson_post_type, ['status' => 403]);
		}

		$update_post = [
			'ID' => $post->ID,
			'post_title' => $args['title'] ?? $post->post_title,
			'post_status' => $args['status'] ?? $post->post_status,
		];

		$updated_id = wp_update_post($update_post);

		if (is_wp_error($updated_id)) {
			return $updated_id;
		}

		if ($args['parent_topic']) {
			update_post_meta($updated_id, '_topic_id', $args['parent_topic']);
		}

		if (!empty($args['meta']) && is_array($args['meta'])) {
			foreach ($args['meta'] as $key => $val) {
				update_post_meta($updated_id, $key, $val);
			}
		}

		$post = get_post($updated_id);

		return rest_ensure_response(['success' => true, 'post' => $post]);
	}

	/**
	 * Deletes a lesson
	 *
	 * @param \WP_REST_Request $req The request.
	 * @return \WP_REST_Response | \WP_Error
	 */
	public function delete_lesson($req)
	{
		$key = $req->get_param('key');

		if (!$key) {
			return new WP_Error('bad_request', "A key needs to be supplied to delete a lesson", ['status' => 400]);
		}

		$args = [
			'post_type' => lighter_lms()->lesson_post_type,
			'post_status' => 'any',
			'meta_query' => [
				[
					'key' => '_lighter_lesson_key',
					'value' => $key,
					'compare' => '='
				],
			],
		];

		$query = new WP_Query($args);

		if (!$query->have_posts()) {
			return new \WP_Error('not_found', "Lesson with Key {$key} not found", ['status' => 404]);
		} elseif ($query->post_count > 1) {
			return new \WP_Error('server_error', "Found multiple lessons with key {$key}", ['status' => 400, 'queried_obj' => $query]);
		}

		$post_id = false;

		while ($query->have_posts()) {
			$query->the_post();
			$post_id = get_the_ID();
		}
		wp_reset_postdata();

		$deleted = wp_delete_post($post_id, true);

		if (!$deleted) {
			return new \WP_Error('delete_failed', "Could not delete lesson with ID {$post_id}", ['status' => 500]);
		}

		return rest_ensure_response(['success' => true, 'deleted' => true, 'id' => $post_id]);
	}

	public function register_topic_routes()
	{
		register_rest_route($this->namespace, '/topic', [
			'methods' => 'POST',
			'callback' => [$this, 'create_topic'],
			'permission_callback' => fn() => current_user_can('edit_posts'),
			'args' => [
				'course_id' => [
					'required' => true,
					'type' => 'integer',
					'min' => 0,
					'description' => 'Post ID of the course which the topic belongs to',
				],
				'key' => [
					'type' => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'description' => 'The unique key of the topic',
				],
				'title' => [
					'type' => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'descripiton' => 'The topic title',
				],
				'sort_order' => [
					'type' => 'integer',
					'default' => 0,
					'min' => 0,
					'description' => 'Indicates where the topic should show compared to others',
				],
			],
		]);

		register_rest_route($this->namespace, '/topic', [
			'methods' => 'GET',
			'callback' => [$this, 'search_topics'],
			'permission_callback' => '__return_true',
			'args' => [
				'q' => [
					'required' => false,
					'type' => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'description' => 'Query topics by title, or course title.',
				],
				'limit' => [
					'type' => 'integer',
					'default' => 20,
					'minimum' => -1,
					'maximum' => 250,
				],
				'page' => [
					'type' => 'integer',
					'default' => 1,
					'minimum' => 1,
				],
			],
		]);

		register_rest_route($this->namespace, '/topic/(?P<key>[a-z0-9]+)', [
			'methods' => 'GET',
			'callback' => [$this, 'get_topic'],
			'permission_callback' => fn() => current_user_can('edit_posts'),
			'args' => [
				'key' => [
					'require' => true,
					'pattern' => '[a-z0-9]+',
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
		]);

		register_rest_route($this->namespace, '/topic/(?P<key>[a-z0-9]+)', [
			'methods' => 'PUT,PATCH',
			'callback' => [$this, 'update_topic'],
			'permission_callback' => fn() => current_user_can('edit_posts'),
		]);

		register_rest_route($this->namespace, '/topic/(?P<key>[a-z0-9]+)', [
			'methods' => 'DELETE',
			'callback' => [$this, 'delete_topic'],
			'permission_callback' => fn() => current_user_can('delete_posts'),
		]);
	}

	/**
	 * Creates a new Topic
	 *
	 * @param \WP_REST_Request $req
	 * @return \WP_REST_Response | \WP_Error
	 */
	public function create_topic($req)
	{
		$args = $req->get_json_params();

		if (! isset($args['course_id'])) {
			return new \WP_Error('creation_failed', 'Cannot create a topic outside a course. No course id passed.', ['status' => 400]);
		}

		$topic_db = new Topics();

		$created_id = $topic_db->create(...$args);

		if (!$created_id) {
			return new \WP_Error('server_error', "Error creating topic", ['status' => 500]);
		}

		if (! $req->get_param('full') || ! isset($args['full'])) {
			return rest_ensure_response(['success' => true, 'id' => $created_id]);
		}

		$topic = $topic_db->get($created_id);
		return rest_ensure_response(['success' => true, 'id' => $created_id, 'topic' => $topic]);
	}

	/**
	 * Fetch mutliple topics.
	 *
	 * @param \WP_REST_Request $req
	 * @return \WP_REST_Response | \WP_Error
	 */
	public function search_topics($req)
	{
		$topic_db = new Topics();

		$search_term = $req->get_param('q');
		$per_page = max(1, min(250, $req->get_param('limit') ?: 20));
		$page = max(1, $req->get_param('page') ?: 1);
		$order_by = $req->get_param('order_by');
		$order = $req->get_param('order');

		$args = ['per_page' => $per_page, 'page' => $page, 'order_by' => $order_by, 'order' => $order];

		if (empty($search_term)) {
			$topics = $topic_db->get(null, $args);

			if (!$topics) {
				return new WP_Error("no_topics", "No topics were found", ['status' => 404]);
			}

			$topics = array_map([$topic_db::class, 'normalise_for_rest'], $topics);

			return rest_ensure_response($topics);
		}

		list($topics, $total) = $topic_db->search($search_term, $args);

		$response = new WP_REST_Response($topics, 200);

		$response->header('topics_total', $total);

		return rest_ensure_response($response);
	}

	/**
	 * Fetches a topic. Optionally with lessons.
	 *
	 * @param \WP_REST_Request $req
	 * @return \WP_REST_Response | \WP_Error
	 */
	public function get_topic($req)
	{
		$args = $req->get_json_params();
		$key = $req->get_param('key');

		$topic_db = new Topics();

		$topic = $topic_db->get($key);

		if (! $req->get_param('with_lessons') || ! isset($args['with_lessons'])) {
			return rest_ensure_response(['topic' => $topic]);
		}

		$lessons = new WP_Query([
			'post_type' => lighter_lms()->lesson_post_type,
			'meta_key' => '_lighter_parent_topic',
			'meta_value' => $key,
			'posts_per_page' => -1,
		]);

		return rest_ensure_response(['topic' => $topic, 'lessons' => $lessons]);
	}

	/**
	 * Updates a topic
	 *
	 * @param \WP_REST_Request $req
	 * @return \WP_REST_Response | \WP_Error
	 */
	public function update_topic($req)
	{
		$args = $req->get_json_params();
		$key = $req->get_param('key');

		$topic_db = new Topics();
		$topic = $topic_db->update($key, $args);

		return rest_ensure_response(['topic' => $topic]);
	}

	/**
	 * Deletes a topic
	 *
	 * @param \WP_REST_Request $req
	 * @return \WP_REST_Response | \WP_Error
	 */
	public function delete_topic($req)
	{
		$key = $req->get_param('key');

		$topic_db = new Topics();

		$success = $topic_db->delete($key);

		if (!$success) {
			return new WP_Error('server_error', "Internal server error. Could not delete topic", ['status' => 500]);
		}

		return rest_ensure_response(['success' => true, 'key' => $key]);
	}

	public function register_course_routes()
	{
		register_rest_field(lighter_lms()->course_post_type, 'tags', [
			'get_callback' => [$this, 'get_courses_tags'],
			'schema' => null,
		]);
		register_rest_field(lighter_lms()->course_post_type, 'columns', [
			'get_callback' => [$this, 'get_courses_columns'],
			'schema' => null,
		], 10, 3);
		register_rest_field(lighter_lms()->course_post_type, 'bulk_actions', [
			'get_callback' => [$this, 'get_courses_bulk_actions'],
			'schema' => null,
		], 10, 3);
		register_rest_field(lighter_lms()->course_post_type, 'course_title', [
			'get_callback' => [$this, 'get_courses_title'],
			'schema' => null,
		]);
	}

	/**
	 * Fetches all course columns
	 *
	 * @param string|array $object
	 * @param string $field_name
	 * @param \WP_REST_Request $req
	 * @return \WP_REST_Response | \WP_Error
	 */
	public function get_courses_columns($object, $field_name, $req)
	{
		$col = $req['columns'] ?? false;

		if (!$col) {
			return null;
		}

		$cols = $this->_get_post_headers(lighter_lms()->course_post_type);

		return $cols;
	}

	/**
	 * Filters the rest query for Courses.
	 *
	 * @param array $args
	 * @param \WP_REST_Request $req
	 */
	public function course_rest($args, $req)
	{
		if (empty($args['status'])) {
			$args['post_status'] = get_post_stati();
		}

		return $args;
	}

	/**
	 * Fetches all courses bulk actions.
	 *
	 * @param string|array $object
	 * @param string $field_name
	 * @param \WP_REST_Request $req
	 * @return \WP_REST_Response | \WP_Error
	 */
	public function get_courses_bulk_actions($object, $field_name, $req)
	{
		$bulks = $req['bulk_actions'] ?? false;

		if (!$bulks) {
			return null;
		}

		$actions = [];

		$post_type_obj = get_post_type_object(lighter_lms()->course_post_type);

		if (current_user_can($post_type_obj->cap->edit_posts)) {
			$actions['untrash'] = __('Restore');
		}

		if (current_user_can($post_type_obj->cap->delete_posts)) {
			$actions['delete'] = __('Delete Permanetly');
			$actions['trash'] = __('Move to Trash');
		}

		return $actions;
	}

	/**
	 * Adds title as a field
	 *
	 * @param string|array $object
	 * @param string $field_name
	 * @param \WP_REST_Request $req
	 * @return \WP_REST_Response | \WP_Error
	 */
	public function get_courses_title($object, $field_name, $req)
	{
		return get_the_title();
	}

	/**
	 * Adds the tags.
	 *
	 * @param string|array $object
	 * @param string $field_name
	 * @param \WP_REST_Request $req
	 * @return \WP_REST_Response | \WP_Error
	 */
	public function get_courses_tags($object, $field_name, $req)
	{
		return wp_get_post_terms($object['id'], 'course-tags', ['fields' => 'names']);
	}

	private function _get_post_headers($post_type)
	{
		$columns = [
			'cb' => __('Select All'),
			'title' => __('Title'),
		];

		foreach (get_object_taxonomies($post_type) as $taxonomy) {
			$tax = get_taxonomy($taxonomy);
			if ($tax->show_admin_column) {
				$columns['taxonomy-' . $taxonomy] = $tax->labels->name;
			}
		}

		if (post_type_supports($post_type, 'author')) {
			$columns['author'] = __('Author');
		}

		if (post_type_supports($post_type, 'comments')) {
			$columns['comments'] = __('Comments');
		}

		$columns['date'] = __('Date');

		return apply_filters("manage_{$post_type}_posts_columns", $columns);
	}

	/**
	 * Ensures title is always on rest requets.
	 *
	 * @param \WP_REST_Response $response
	 * @param \WP_Post $post
	 * @param \WP_REST_Request $request
	 */
	public function ensure_fields($response, $post, $request)
	{
		if (!in_array($post->post_type, lighter_lms()->post_types)) {
			return $response;
		}

		if (empty($response->data['title'])) {
			$response->data['title'] = ['rendered' => get_post_field('post_title', $post, 'raw') ?: '']; // NOTE: raw value as Svelte will escape it.
		}

		return $response;
	}

	public function ensure_user($resp, $user, $req)
	{
		$data = $resp->get_data();
		$current_user_id = get_current_user_id();

		$can_view = $current_user_id === $user->ID || current_user_can('edit_users') || current_user_can('list_users');

		if ($can_view) {
			$data['email'] = $user->user_email;
		} else {
			unset($data['email']);
		}

		$resp->set_data($data);
		return $resp;
	}
}
