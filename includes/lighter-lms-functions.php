<?php

use LighterLMS\Import_Scheduler;
use LighterLMS\Types;
use LighterLMS\Lessons;
use LighterLMS\Randflake;
use LighterLMS\Topics;
use LighterLMS\User_Access;
use LighterLMS\WooCommerce\WC;

if (! defined('ABSPATH')) {
	exit;
}

if (! function_exists('lighter_lms')) {
	/**
	 * The lighter LMS config object
	 *
	 * @return \LighterLMS\Core\Config
	 */
	function lighter_lms()
	{
		return \LighterLMS\Core\Config::get_instance();
	}
}

add_action('after_setup_theme', function () {
	add_image_size('lighter_course_main', 450);
});

if (!function_exists('lighter_get_path')) {
	function lighter_get_path($relpath = '')
	{
		return LIGHTER_LMS_PATH . ltrim($relpath, '/');
	}
}

if (! function_exists('lighter_include')) {
	function lighter_include($relpath = '', $args = [])
	{
		if (strpos($relpath, LIGHTER_LMS_PATH) !== 0) {
			$relpath = lighter_get_path($relpath);
		}

		if (file_exists($relpath)) {
			extract($args, EXTR_SKIP);
			include $relpath;
		}
	}
}

if (! function_exists('lighter_view')) {
	function lighter_view($path, $args = [])
	{
		$is_admin = $args['admin'] ?? false;
		$admin_path = $is_admin ? 'admin/' : '';

		if (substr($path, -4) !== '.php') {
			$path = 'includes/' . $admin_path . 'views/' . $path . '.php';
		} else {
			$path = 'includes/' . $admin_path . 'views/' . $path;
		}

		lighter_include($path, $args);
	}
}

if (! function_exists('lighter_icon')) {
	function lighter_icon($name)
	{
		if (substr($name, -4) !== '.svg') {
			$path = "assets/icons/$name.svg";
		} else {
			$path = "assets/icons/$name";
		}

		lighter_include($path);
	}
}

if (! function_exists('lighter_attrify')) {
	/**
	 * Parses a string as an attribute. "Lighter LMS" -> "lighter-lms"
	 *
	 * @param mixed $value The input to parse
	 * @param string $seperator="-" The string to use as a seperator
	 * @return string The attributified string
	 */
	function lighter_attrify($value, $seperator = "-")
	{
		return strtolower(preg_replace(["/\s/", "/_/", "/-/"], $seperator, $value));
	}
}

if (! function_exists('lighter_get_course_lessons')) {
	/**
	 * Get course lessons
	 *
	 * Retrieves all the lessons for a given course, as well as their meta data.
	 *
	 * @param \WP_Post|int $course The course
	 * @param \WP_User|int $user The user
	 * @param bool $with_topics Whether to return the topic field.
	 *
	 * @phpstan-import-type CourseData from Types
	 *
	 * @return CourseData
	 */
	function lighter_get_course_lessons($course, $user = null, $with_topics = false)
	{
		$course = get_post($course);

		$lesson_db = new Lessons(['parent' => $course->ID]);
		$lessons = array_map(fn($post) => [
			'id' => $post->ID,
			'key' => get_post_meta($post->ID, '_lighter_lesson_key', true),
			'slug' => $post->post_name,
			'title' => $post->post_title,
			'sortOrder' => get_post_meta($post->ID, '_lighter_sort_order', true),
			'parentTopicKey' => get_post_meta($post->ID, '_lighter_parent_topic', true),
		], $lesson_db->get_lessons());

		$user_access = new User_Access($user);
		$owned = $user_access->get_owned($course);
		$progress = $user_access->get_progress($course);

		$data = ['lessons', 'owned', 'progress'];

		if ($with_topics) {
			$topics_db = new Topics();
			$topics = array_map(function ($row) {
				return [
					'key' => $row->topic_key,
					'title' => $row->title,
					'sortOrder' => $row->sort_order,
					'courseId' => $row->post_id,
				];
			}, $topics_db->get_by_course($course->ID));
			usort($topics, fn($a, $b) => (int)$a['sortOrder'] - (int)$b['sortOrder']);
			$data[] = 'topics';
		}
		return compact($data);
	}
}

if (! function_exists('lighter_save_product')) {
	/**
	 * Save product
	 *
	 * Saves a product, optionally to a certain post.
	 *
	 * @param object $args The product object to save.
	 * @param int? $post_id The id of the post to save it to. 0 will not save it to a post.
	 */
	function lighter_save_product($args, $post_id = 0)
	{
		$store = lighter_lms()->connected_store;

		if ('woocommerce' === $store) {
			return WC::save_product($args, $post_id);
		}
	}
}

if (! function_exists('ligter_get_course_product')) {
	/**
	 * Gets a product
	 *
	 * Get the product obj with Lighter fields
	 *
	 * @param int $product_id
	 * @return object
	 */
	function lighter_get_course_product($product_id)
	{
		if (! $product_id) {
			_doing_it_wrong(__FUNCTION__, 'Cannot fetch product of empty product id', '1.0');
			return (object) [];
		}

		if ("woocommerce" === lighter_lms()->connected_store) {
			return WC::get_product($product_id);
		}
	}
}

if (! function_exists('lighter_get_course_settings')) {
	/**
	 * Get course settings
	 *
	 * Returns the settings for a given course id
	 *
	 * @param int $post Post id or post object. Defaults to the global post.
	 * @return array|bool The settings as an associative array. False if it fails.
	 */
	function lighter_get_course_settings($post = 0)
	{
		$course = get_post($post);

		$post_id = $course->ID ?? 0;
		$thumbnail_id = get_post_thumbnail_id($post_id);

		if ($course->post_type != lighter_lms()->course_post_type) {
			return false;
		}

		$product_id = get_post_meta($post_id, '_lighter_product_id', true);

		$settings = [
			'currency' => lighter_lms()->defaults()->currency,
			'status' => $course->post_status,
			'showIcons' => get_post_meta($post_id, '_lighter_show_lesson_icons', true) ?: lighter_lms()->defaults()->course_show_lesson_icons,
			'showProgress' => get_post_meta($post_id, '_lighter_show_lesson_prog', true) ?: lighter_lms()->defaults()->course_show_progress,
			'publishedOn' => $course->post_date_gmt,
			'userLocale' => str_replace("_", "-", get_user_locale()),
			'description' => get_post_meta($post_id, '_course_description', true),
			'displayHeader' => get_post_meta($post_id, '_course_display_theme_header', true) ?: lighter_lms()->defaults()->course_hide_theme_header,
			'displaySidebar' => get_post_meta($post_id, '_course_display_theme_sidebar', true) ?: lighter_lms()->defaults()->course_hide_theme_sidebar,
			'displayFooter' => get_post_meta($post_id, '_course_display_theme_footer', true) ?: lighter_lms()->defaults()->course_hide_theme_footer,
			'product' => lighter_get_course_product($product_id),
			'store' => lighter_lms()->connected_store,
			'sync_prod_img' => get_post_meta($post_id, '_course_sync_prod_img', true) ?: lighter_lms()->defaults()->course_sync_prod_img,
			'editor' => lighter_lms()->defaults()->editor,
			'baseUrl' => 'kurser',
			'slug' => $course->post_name
		];

		if ($settings['store'] === "woocommerce") {
			$settings['currency'] = \get_woocommerce_currency();
		}

		if ($thumbnail_id) {
			$settings['thumbnail'] = [
				'id' => $thumbnail_id,
				'src' => wp_get_attachment_url($thumbnail_id),
				'alt' => get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true) ?: null,
			];
		}

		return $settings;
	}
}

if (!function_exists('lighter_course_sidebar')) {
	/**
	 * Creates and prints the sidebar of a course to be viewed on the frontend.
	 *
	 * @param WP_Post|int $post The course to generate the sidebar of.
	 * @param bool $display Whether to display the outout. Will return the generated HTML if false.
	 *
	 * @return string|null The generated HTML.
	 */
	function lighter_course_sidebar($course, $display = true)
	{
		$post = get_post($course);

		if (is_admin() || $post->post_type !== lighter_lms()->course_post_type) {
			return;
		}

		$course_data = lighter_get_course_lessons($course, null, true);

		$sidebar = [['title' => $post->post_title, 'href' => get_permalink($post)]];
		foreach ($course_data['topics'] as $topic) {
			$filtered = array_filter($course_data['lessons'], fn($lesson) => $lesson['parentTopicKey'] === $topic['key']);
			usort($filtered, fn($a, $b) => (int)$a['sortOrder'] - (int)$b['sortOrder']);
			$sidebar[] = [
				...$topic,
				'lessons' => array_values($filtered),
			];
		}


		if (!$display) {
			ob_start();
		}
?>
		<div class="lighterlms nav-wrap course-sidebar">
			<?php do_action('lighter_lms_course_before_topics_nav'); ?>
			<nav class="course-nav lighterlms">
				<ul class="course-topics">
					<?php foreach ($sidebar as $sidebar_item) :
						if (array_key_exists('lessons', $sidebar_item)):
							lighter_sidebar_item($sidebar_item, $course_data);
						else: ?>
							<li>
								<h1><a href="<?php echo esc_attr(esc_url($sidebar_item['href'])) ?>"><?php echo esc_html($sidebar_item['title']) ?></a></h1>
							</li>
					<?php endif;
					endforeach;
					?>
				</ul>
			</nav>
			<?php do_action('lighter_lms_course_after_topics_nav'); ?>
		</div>
		<?php
		if (!$display) {
			$out = ob_get_clean();
			return $out;
		}
	}
}

if (!function_exists('lighter_sidebar_item')) {
	/**
	 * Generate html for a sidebar item.
	 *
	 * @param array $item The sidebar item
	 * @param CourseData $data The course data
	 * @param bool $display Whether to ouput the content. Will be returned if not output.
	 *
	 * @return string|null The generated content.
	 */
	function lighter_sidebar_item($item, $data, $display = true)
	{
		if (!$display) {
			ob_start();
		} ?>
		<li class="lighter-topic" data-key="<?php echo esc_attr($item['key']) ?>">
			<h3>
				<button type="button" aria-expanded="true" aria-controls="<?php echo strtolower(esc_attr($item['title'])) ?>-lessons" class="togglable-btn">
					<?php echo esc_html($item['title']) ?>
				</button>
			</h3>
			<ul class="course-lessons open">
				<?php foreach ($item['lessons'] as $lesson) {
					$completed = '';
					if (isset($data['progress']) && !empty($data['progress'])) {
						$completed = in_array($lesson['id'], $data['progress']['completed_lessons']) ? '<span class="lesson-completed" title="Lesson completed">✓<span class="screen-reader-text">Lesson comlpeted</span></span>' : '';
					}
					if (in_array($lesson['id'], $data['owned']['lessons'] ?? []) || current_user_can('manage_options')) {
						printf(
							'<li><a href="?lesson=%1$s" class="course-lesson %1$s" data-lesson="%1$s" data-lesson-id="%2$s" data-key="%3$s" data-parent-key="%4$s">%5$s</a>%6$s</li>',
							strtolower(sanitize_key($lesson['slug'])),
							esc_attr($lesson['id']),
							esc_attr($lesson['key']),
							esc_attr($lesson['parentTopicKey']),
							esc_html($lesson['title']),
							$completed
						);
					} else {
						printf('<li><span class="lighter-not-owned">%s</span></li>', esc_html($lesson['title']));
					}
				} ?>
			</ul>
		</li>
<?php
		if (!$display) {
			return ob_get_clean();
		}
	}
}

if (!function_exists('lighter_normalise_posts')) {
	/**
	 * Normilises a post to use in LighterLMS
	 *
	 * @phpstan-import-type PostNorm from Types
	 *
	 * @param array<int, \WP_Post> $posts An array of wp posts.
	 * @return array<int, PostNorm>
	 */
	function lighter_normalise_posts($posts)
	{
		/**
		 * @param WP_Post $post
		 * 
		 *
		 * @return PostNorm
		 */
		return array_map(function ($post) {
			$post_type_obj = get_post_type_object($post->post_type);
			return [
				'id' => $post->ID,
				'title' => $post->post_title,
				'date' => $post->post_date,
				'date_gmt' => $post->post_date_gmt,
				'modified' => $post->post_modified,
				'modified_gmt' => $post->post_modified_gmt,
				'link' => site_url("{$post_type_obj->rewrite['slug']}/{$post->post_name}"),
				'slug' => $post->post_name,
				'status' => $post->post_status,
				'tags' => wp_get_post_terms($post->ID, 'course-tags', ['fields' => 'names']),
				'type' => $post->post_type,
			];
		}, $posts);
	}
}

if (! function_exists('lighter_parse_post_stati')) {
	function lighter_parse_post_stati($query)
	{
		$stati = [];

		if (!isset($query->request) || empty($query->request)) {
			error_log('LighterLMS: Tried to parse stati from an empty or missing request');
			return $stati;
		}

		if (preg_match_all("/wp_posts\.post_status\s*=\s*'([^']+)'/", $query->request, $matches)) {
			$stati = $matches[1];
		}

		return array_unique($stati);
	}
}

if (! function_exists('lighter_postlist_js_obj')) {
	function lighter_postlist_js_obj($post_type)
	{
		global $wp_query;

		$screen = get_current_screen();
		$column_headers = get_column_headers($screen);
		$actions = [];
		$post_type_obj = get_post_type_object($post_type);
		$per_page = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
		$per_page = max(1, min($per_page, 100));

		$all_tags = get_terms(['taxonomy' => 'course-tags']);
		$all_tags = array_map(fn($tag) => [
			'id' => $tag->term_id,
			'name' => $tag->name,
			'count' => $tag->count,
			'slug' => $tag->slug,
			'taxonomy' => $tag->taxonomy
		], $all_tags);

		$filters = [
			'post_type' => $wp_query->query['post_type'],
			'post_stati' => lighter_parse_post_stati($wp_query),

			'query' => $wp_query,
		];

		if (current_user_can($post_type_obj->cap->edit_posts)) {
			$actions['untrash'] = __('Restore');
		}

		if (current_user_can($post_type_obj->cap->delete_posts)) {
			$actions['delete'] = __('Delete Permanetly');
			$actions['trash'] = __('Move to Trash');
		}

		$obj = [
			'actions' => $actions,
			'columns' => $column_headers,
			'pagination' => [
				'page' => isset($_GET['paged']) ? intval($_GET['paged']) : 1,
				'totalPages' => ceil(intval($wp_query->found_posts) / $per_page),
				'totalPosts' => $wp_query->found_posts,
				'limit' => isset($_GET['limit']) ? intval($_GET['limit']) : 20,
			],
			'posts' => lighter_normalise_posts($wp_query->posts),
			'tags' => [
				'all' => $all_tags,
			],
			'filters' => $filters,
		];

		return wp_json_encode($obj);
	}
}

if (! function_exists('lighter_get_lesson_settings')) {
	function lighter_get_lesson_settings($post = 0)
	{
		$topic_db = new Topics();
		$post = get_post($post);

		$post_id = $post->ID ?? 0;

		$parent = get_post_meta($post_id, '_lighter_parent_topic', true);
		if ($parent) {
			$topic = $topic_db->get($parent);

			$parents[] = [$topic->topic_key => $topic->title];
		}

		return [
			'parents' => $parents ?? [],
			'slug' => $post->post_name,
		];
	}
}

if (!function_exists('lihgter_grant_course_access')) {
	/**
	 * Give course access to a specified user
	 *
	 * @param int $course_id The id of the course to give access to.
	 * @param int|\WP_User|null $user The ID or user object to give access to. Defaults to the logged in user.
	 * @param int[] $lessons Array of lesson IDs to give access to.
	 */
	function lighter_grant_course_access($course_id, $user = null, $lessons)
	{
		$user = new User_Access($user);
		$user->grant_course_access($course_id, 'partial', $lessons);
	}
}

if (!function_exists('lighter_sanitize_access')) {
	/**
	 * Sanitize access object.
	 *
	 * Sanitizes an access object, making sure all items are valid lesson ID's, and the key's are valid topic keys.
	 *
	 * @param object|array	$access		The access object to sanitize
	 * @param int			$post_id	The course post ID.
	 * 
	 * @return array The sanitized access object.
	 */
	function lighter_sanitize_access($access, $post_id)
	{
		if ($access == null || empty($access)) {
			return [];
		}

		$topic_db = new Topics();
		$access_obj = [];

		foreach ($access as $key => $post_ids) {
			$topic = $topic_db->get($key);

			if (empty($topic) || $topic->post_id != $post_id) {
				continue;
			}

			$post_ids = array_filter(array_map(function ($id) {
				if ($id === null || !is_scalar($id)) return null;
				if (is_int($id)) return $id;
				if (is_numeric($id)) return (int) $id;
				if (!Randflake::validate($id)) return null;

				$clean_id = sanitize_text_field($id);
				$post = get_post([
					'post_type' => lighter_lms()->lesson_post_type,
					'post_status' => 'any',
					'meta_query' => [
						[
							'key' => '_lighter_lesson_key',
							'value' => $clean_id,
							'compare' => '=',
						],
					],
					'posts_per_page' => 1,
					'fields' => 'ids',
					'suppress_filters' => true,
				]);
				return (empty($post)) ? null : $post->ID;
			}, $post_ids));

			$access_obj[$key] = $post_ids;
		}

		return $access_obj;
	}
}

add_action('lighter_lms_import_user', function ($row, $opts) {
	$fname = sanitize_text_field($row[0] ?? '');
	$lname = sanitize_text_field($row[1] ?? '');
	$email = sanitize_email($row[2] ?? '');
	$phone = sanitize_text_field($row[3] ?? '');
	$address = [
		'first_name' => $fname,
		'last_name' => $lname,
		'email' => $email,
		'phone' => $phone,
	];
	if (!$opts['userName']) { // Account for whether or not username is email.
		$skus = trim($row[5] ?? '');
		$address = [
			...$address,
			'address_1' => sanitize_text_field($row[6] ?? ''),
			'city' => sanitize_text_field($row[7] ?? ''),
			'postcode' => sanitize_text_field($row[8] ?? ''),
			'country' => sanitize_text_field($row[9] ?? '')
		];
		$notes = sanitize_text_field($row[10] ?? '');
		$date = sanitize_text_field($row[11] ?? '');
	} else {
		$skus = trim($row[4] ?? '');
		$address = [
			...$address,
			'address_1' => sanitize_text_field($row[5] ?? ''),
			'city' => sanitize_text_field($row[6] ?? ''),
			'postcode' => sanitize_text_field($row[7] ?? ''),
			'country' => sanitize_text_field($row[8] ?? '')
		];
		$notes = sanitize_text_field($row[9] ?? '');
		$date = sanitize_text_field($row[10] ?? '');
	}

	if (!is_email($email)) {
		throw new Exception("Invalid email address; " . ($row[2] ?? ''));
	}

	$user = get_user_by('email', $email);

	if (!$user && $opts['skipNew']) {
		return;
	} else if (!$user) {
		$username = !$opts['userName'] ? sanitize_text_field($row[4] ?? '') : $email;
		$pass = wp_generate_password();

		$user_id = wp_create_user($username, $pass, $email);

		if (is_wp_error($user_id)) {
			throw new Exception("Could not create user; " . $user_id->get_error_message());
		}

		$user = get_user_by('id', $user_id);

		if ($opts['notify']) {
			// TODO: Send notification email.
		}
	}

	update_user_meta($user->ID, 'billing_phone', $phone);
	update_user_meta($user->ID, 'first_name', $fname);
	update_user_meta($user->ID, 'last_name', $lname);

	// TODO: Handle other stores than just Woo.
	if (lighter_lms()->connected_store === "woocommerce") {
		error_log(var_export($opts, true));
		if ($opts['createOrders'] && !empty($skus)) {
			WC::create_legacy_orders($user, $skus, $address, $notes, $date);
		}
	}
}, 10, 2);

add_action('lighter_process_import_batch', function ($job_id) {
	$job = get_option('lighter_job_' . $job_id);

	if (!$job || $job['status'] !== 'running' || !file_exists($job['file_path'])) {
		error_log("Lighter Error: Tried to process unfound import of id " . $job_id);
		return;
	}

	$batch_size = 50;
	$process_count = 0;

	if (function_exists('set_time_limit')) {
		set_time_limit(300);
	}

	try {
		$file = new SplFileObject($job['file_path']);
		$file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);

		$file->seek($job['current_line']);

		if ($file->key() !== $job['current_line']) {
			$job['status'] = 'completed';
			update_option('lighter_job_' . $job_id, $job, false);
			return;
		}

		while (! $file->eof() && $process_count < $batch_size) {
			$row = $file->current();

			if ($job['current_line'] === 0 && !empty($job['opts']['firstHeader'])) {
				$job['current_line']++;
				$file->next();
				continue;
			}

			if (!empty($row)) {
				try {
					/**
					 * Imports a user. Erros should be thrown, so they are picked up
					 * and sent to the front end automatically.
					 *
					 * @param array $row The row of user data.
					 * @param array $job['options'] The options of the current import job.
					 */
					do_action('lighter_lms_import_user', $row, $job['opts']);
				} catch (Exception $e) {
					$job['errors'][] = "Line {$job['current_line']}: " . $e->getMessage();
				}
			}

			$job['current_line']++;
			$process_count++;
			$file->next();
		}

		update_option('lighter_job_' . $job_id, $job, false);

		if ($file->eof() || $job['current_line'] >= $job['total_lines']) {
			$job['status'] = 'completed';
			return update_option('lighter_job_' . $job_id, $job, false);
		} else {
			Import_Scheduler::schedule_batch($job_id, true);
		}
	} catch (Exception $e) {
		$job['status'] = 'failed';
		$job['errors'][] = "Fatal error: " . $e->getMessage();
		update_option('lighter_job_' . $job_id, $job, false);
	}
});
