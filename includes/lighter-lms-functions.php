<?php

use LighterLMS\Lessons;
use LighterLMS\Topics;

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
			return lighter_save_wc_product($args, $post_id);
		}
	}
}

if (! function_exists('lighter_save_wc_product')) {
	/**
	 * Save product to woocommerce
	 *
	 * Saves a product as a woocommerce product. Updates the product if $args contains 'id'.
	 *
	 * @param object $args The product object to save.
	 * @param int? $post_id The id of the post to save it to. 0 will not save it to a post.
	 */
	function lighter_save_wc_product($args, $post_id)
	{
		if (! did_action('woocommerce_init')) {
			_doing_it_wrong(__FUNCTION__, 'WooCommerce was not initialised', '1.0');
			return 0;
		}

		$product_id = $args['id'];

		$product = isset($args['id']) ? \wc_get_product_object('simple', $args['id']) : new \WC_Simple_Product();

		$auto_comp = $args['auto_comp'];
		$auto_hide = $args['auto_hide'];

		update_post_meta($product_id, '_lighter_lms_wc_auto_complete_course', $auto_comp);
		update_post_meta($product_id, '_lighter_lms_course_hide_in_store', $auto_hide);

		$img_id = $args['images'][0]['id'] ?? false;

		unset($args['auto_comp']);
		unset($args['auto_hide']);
		unset($args['id']);
		unset($args['images']);

		foreach ($args as $key => $arg) {
			$method = 'set_' . $key;
			$product->$method($arg);
		}

		if ($img_id) {
			$product->set_image_id($img_id);
		}

		if ($post_id > 0) {
			update_post_meta($post_id, '_lighter_product_id', $product_id);
		}

		$product->set_virtual(true);
		$product->save();

		return 1;
	}
}

if (! function_exists('ligter_get_course_product')) {
	function lighter_get_course_product($product_id)
	{
		if (! $product_id) {
			_doing_it_wrong(__FUNCTION__, 'Cannot fetch product of empty product id', '1.0');
			return (object) [];
		}

		if (! did_action('woocommerce_init')) {
			_doing_it_wrong(__FUNCTION__, 'WooCommerce was not initialised', '1.0');
		}

		$product = \wc_get_product_object('simple', $product_id);

		if (empty($product)) {
			return (object) [];
		}

		$auto_comp = get_post_meta($product_id, '_lighter_lms_wc_auto_complete_course', true) ?: lighter_lms()->defaults()->course_auto_complete;
		$auto_hide = get_post_meta($product_id, '_lighter_lms_course_hide_in_store', true) ?: lighter_lms()->defaults()->course_auto_hide;

		$image_id = $product->get_image_id();
		$image = [[
			"src" => wp_get_attachment_url($image_id) ?: null,
			"alt" => wp_get_attachment_caption($image_id) ?: null,
			"id" => $image_id ?: null,
		]];

		$downloads = array_values(
			array_map(
				fn($prod) => [
					"name" => sanitize_text_field($prod['name']),
					"file" => esc_url($prod['file'])
				],
				$product->get_downloads('edit')
			)
		);

		$obj = [
			'auto_hide' => $auto_hide,
			'auto_comp' => $auto_comp,
			'id' => $product->get_id('edit'),
			'name' => $product->get_name('edit'),
			'regular_price' => $product->get_regular_price('edit'),
			'sale_price' => $product->get_sale_price('edit'),
			'images' => $image,
			'downloads' => $downloads,
			'description' => $product->get_description('edit'),
			'short_description' => $product->get_short_description('edit'),
			'stock_quantity' => $product->get_stock_quantity('edit'),
		];

		return $obj;
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
			'editor' => lighter_lms()->defaults()->editor,
			'baseUrl' => 'kurser',
			'slug' => $course->post_name
		];

		if ($settings['store'] === "woocommerce") {
			$settings['currency'] = \get_woocommerce_currency();
		}

		return $settings;
	}
}

if (!function_exists('lighterlms_course_sidebar')) {
	/**
	 * Creates and prints the sidebar of a course to be viewed on the frontend.
	 *
	 * @param WP_Post|int $post The course to generate the sidebar of.
	 * @param bool $display Whether to display the outout. Will return the generated HTML if false.
	 *
	 * @return string|null The generated HTML.
	 */
	function lighterlms_course_sidebar($course, $display = true)
	{
		$post = get_post($course);

		if (is_admin() || $post->post_type !== lighter_lms()->course_post_type) {
			return;
		}

		$topic_db = new Topics();

		$topics_raw = $topic_db->get_by_course($post->ID);
		$topics = array_map(function ($row) {
			return [
				'key' => $row->topic_key,
				'title' => $row->title,
				'sortOrder' => $row->sort_order,
				'courseId' => $row->post_id,
			];
		}, $topics_raw);

		usort($topics, fn($a, $b) => (int)$a['sortOrder'] - (int)$b['sortOrder']);

		$lessons = get_posts([
			'post_type' => lighter_lms()->lesson_post_type,
			'numberposts' => -1,
			'lighter_course' => $post->ID,
			'suppress_filters' => false
		]);

		// NOTE: There should be a better way to get the post and metadata
		// directly in one query instead of mapping it after.
		$lesson_data = array_map(function ($lesson) {
			return [
				'id' => $lesson->ID,
				'key' => get_post_meta($lesson->ID, '_lighter_lesson_key', true),
				'slug' => $lesson->post_name,
				'title' => $lesson->post_title,
				'sortOrder' => get_post_meta($lesson->ID, '_lighter_sort_order', true) ?: 0,
				'parentTopicKey' => get_post_meta($lesson->ID, '_lighter_parent_topic', true),
			];
		}, $lessons);

		$sb = [['title' => $post->post_title, 'href' => get_permalink($post)]];
		foreach ($topics as $topic) {
			$filtered = array_filter($lesson_data, fn($lesson) => $lesson['parentTopicKey'] === $topic['key']);
			usort($filtered, fn($a, $b) => (int)$a['sortOrder'] - (int)$b['sortOrder']);
			$sb[] = [
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
					<?php foreach ($sb as $sb_item) :
						if (array_key_exists('lessons', $sb_item)): ?>
							<?php printf('<li class="lighter-topic" data-key="%s">', $sb_item['key']); ?>
							<h3>
								<button type="button" aria-expanded="true" aria-controls="<?= strtolower(esc_attr($sb_item['title'])) ?>-lessons" class="togglable-btn">
									<?= esc_html($sb_item['title']) ?>
								</button>
							</h3>
							<ul class="course-lessons open">
								<?php foreach ($sb_item['lessons'] as $lesson) {
									printf(
										'<li><a href="?lesson=%1$s" class="course-lesson %1$s" data-lesson="%1$s" data-lesson-id="%2$s" data-key="%3$s" data-parent-key="%4$s">%5$s</a></li>',
										strtolower(sanitize_key($lesson['slug'])),
										$lesson['id'],
										$lesson['key'],
										$lesson['parentTopicKey'],
										$lesson['title']
									);
								} ?>
							</ul>
							</li>
						<?php else: ?>
							<li>
								<h1><a href="<?= esc_attr(esc_url($sb_item['href'])) ?>"><?= $sb_item['title'] ?></a></h1>
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

if (!function_exists('lighter_normalise_posts')) {
	function lighter_normalise_posts($posts)
	{
		/**
		 * @param WP_Post $post
		 */
		return array_map(function ($post) {
			$title = 'title';
			$post_type_obj = get_post_type_object($post->post_type);

			if (lighter_lms()->course_post_type === $post->post_type) {
				$title = 'course_title';
			}

			return [
				'id' => $post->ID,
				$title => $post->post_title,
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
				'totalPages' => ceil(intval($wp_query->post_count) / $per_page),
				'totalPosts' => $wp_query->post_count,
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
