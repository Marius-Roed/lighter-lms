<?php

namespace LighterLMS\WooCommerce;

class WC
{
	public function __construct()
	{
		if (!did_action('woocommerce_init')) {
			_doing_it_wrong(__CLASS__, 'Woocommerce was not initialized', '1.0');
			return;
		}
	}

	/**
	 * Save product to woocommerce
	 *
	 * Saves a product as a woocommerce product. Updates the product if $args contains 'id'.
	 *
	 * @param object $args The product object to save.
	 * @param int? $post_id The id of the post to save it to. 0 will not save it to a post.
	 */
	public static function save_product($args, $post_id)
	{
		if (! did_action('woocommerce_init')) {
			_doing_it_wrong(__FUNCTION__, 'WooCommerce was not initialised', '1.0');
			return 0;
		}

		$term_id = term_exists(lighter_lms()->course_slug);

		if (empty($term_id)) {
			$cat = ucwords(str_replace('-', ' ', lighter_lms()->course_slug));
			$term_arr = wp_insert_term(
				$cat,
				'product_cat',
				[
					'description' => __('Courses made with Lighter LMS', 'textdomain'),
					'slug' => lighter_lms()->course_slug,
				]
			);
			if (is_wp_error($term_arr)) {
				error_log("LighterLMS: Error creating woo category {$cat}; {$term_arr->get_error_message()}");
			}
			$term_id = $term_arr['term_id'];
		}

		if ($args['tags']) {
			$tags = wp_set_post_terms($post_id, $args['tags'], 'product_tag');
			if (!$tags || is_wp_error($tags)) {
				error_log("LighterLMS: Error adding tags to WooCommerce product.");
			} else {
				$args['tag_ids'] = $tags;
			}
		}

		$product_id = $args['id'];

		$product = isset($args['id']) ? \wc_get_product_object('simple', $args['id']) : new \WC_Simple_Product();
		$product_id = $product->get_id();

		$auto_comp = $args['auto_comp'];
		$auto_hide = $args['auto_hide'];
		$course_access = lighter_sanitize_access($args['access'], $post_id);

		update_post_meta($product_id, '_lighter_lms_wc_auto_complete_course', $auto_comp);
		update_post_meta($product_id, '_lighter_lms_course_hide_in_store', $auto_hide);
		update_post_meta($product_id, '_lighter_lms_course_access', $course_access); // TODO: Change this to be a separate function

		$img_id = $args['images'][0]['id'] ?? false;

		$args['downloads'] = empty($args['downloads']) ? [] : $args['downloads'];

		unset($args['auto_comp']);
		unset($args['auto_hide']);
		unset($args['id']);
		unset($args['images']);
		unset($args['tags']);
		unset($args['access']);

		foreach ($args as $key => $arg) {
			self::_sanitize_field($key, $arg);
			$method = 'set_' . $key;
			$product->$method($arg);
		}

		if ($img_id) {
			$product->set_image_id($img_id);
		}

		$product->set_category_ids([$term_id]);
		$product->set_virtual(true);
		$product->save();

		if ($post_id > 0) {
			update_post_meta($post_id, '_lighter_product_id', $product->get_id());
		}

		return 1;
	}

	/**
	 * Get a woocommerce product
	 *
	 * Wrapper function returning a woocommerce function with Lighter fields
	 *
	 * @param int $product_id
	 * @return object
	 */
	public static function get_product($product_id)
	{
		if (! did_action('woocommerce_init')) {
			_doing_it_wrong(__FUNCTION__, 'WooCommerce was not initialised', '1.0');
		}

		$product = \wc_get_product_object('simple', $product_id);

		if (empty($product)) {
			return (object) [];
		}

		$auto_comp = get_post_meta($product_id, '_lighter_lms_wc_auto_complete_course', true) ?: lighter_lms()->defaults()->course_auto_complete;
		$auto_hide = get_post_meta($product_id, '_lighter_lms_course_hide_in_store', true) ?: lighter_lms()->defaults()->course_auto_hide;
		$access = get_post_meta($product_id, '_lighter_lms_course_access', true) ?: (object) [];

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
			'access' => $access,
			'id' => $product->get_id('edit'),
			'name' => $product->get_name('edit'),
			'regular_price' => $product->get_regular_price('edit'),
			'sale_price' => $product->get_sale_price('edit'),
			'images' => $image,
			'downloads' => $downloads,
			'description' => $product->get_description('edit'),
			'short_description' => $product->get_short_description('edit'),
			'stock_quantity' => $product->get_stock_quantity('edit'),
			'menu_order' => $product->get_menu_order('edit'),
		];

		return $obj;
	}

	/**
	 * Sanitize product field
	 *
	 * @paam string $key - The name of the field
	 * @param any $val - The value of the field
	 */
	private static function _sanitize_field($key, &$val)
	{
		switch ($key) {
			case 'name':
			case 'price':
			case 'sale_price':
				$val = sanitize_text_field($val);
				break;
			case 'description':
			case 'short_description':
				$val = wp_kses_post($val);
				break;
			case 'menu_order':
				$val = (int) $val;
				break;
			case 'slug':
				$val = sanitize_title($val);
				break;
		}
	}
}
