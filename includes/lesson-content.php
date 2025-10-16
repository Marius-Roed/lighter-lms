<?php

namespace LighterLMS;

class Lesson_Content
{

	public static function get_builder($post_id)
	{
		if (get_post_meta($post_id, '_elementor_edit_mode', true) === 'builder') {
			return 'elementor';
		}

		if (get_post_meta($post_id, '_fl_builder_enabled', true)) {
			return 'beaver-builder';
		}

		if (get_post_meta($post_id, '_et_pb_use_builder', true) === 'on') {
			return 'divi';
		}

		$content = get_post_field('post_content', $post_id);
		if (has_blocks($content)) {
			return 'gutenberg';
		}

		return 'classic-editor';
	}

	public static function handle_elementor_content($post_id, $req_post)
	{
		if (!class_exists('\Elementor\Plugin')) {
			return self::get_content($req_post);
		}

		if (!did_action('elementor/loaded')) {
			return self::get_content($req_post);
		}

		try {
			$elementor_data = get_post_meta($post_id, '_elementor_data', true);
			if (empty($elementor_data)) {
				return self::get_content($req_post);
			}

			global $post;
			$original_post = $post;
			$post = $saved_post = get_post($post_id);
			setup_postdata($saved_post);

			$elementor = \Elementor\Plugin::instance();

			if (!$elementor->frontend) {
				$elementor->init_common();
			}

			$content = $elementor->frontend->get_builder_content($post_id, true);

			wp_reset_postdata();
			$post = $original_post;

			return $content ?: self::get_content($req_post);
		} catch (\Exception $e) {
			error_log('Elementor content error: ' . $e->getMessage());
			return self::get_content($req_post);
		}
	}

	public static function handle_gutenberg_content($post_id, $post)
	{
		$content = get_post_field('post_content', $post_id);

		if (function_exists('parse_blocks')) {
			$blocks = parse_blocks($content);
			$rendered = '';

			foreach ($blocks as $block) {
				$rendered .= render_block($block);
			}

			return $rendered;
		}

		return apply_filters('the_content', $content);
	}

	/**
	 * Gets the standard post content
	 *
	 * @param \WP_Post $post
	 * @return The post content already filtered.
	 */
	public static function get_content($post)
	{
		$content = apply_filters('the_content', $post->post_content);
		return $content;
	}

	public static function get_styles($post_id, $builder)
	{
		$styles = [];
		switch ($builder) {
			case 'elementor':
				if (class_exists('\Elementor\Core\Files\CSS\Post')) {
					$css_file = new \Elementor\Core\Files\CSS\Post($post_id);
					$styles[] = $css_file->get_url();
				}
				break;
			case 'beaver-builder':
				if (class_exists('FL_Builder')) {
					$styles[] = \FLBuilder::get_css_url($post_id);
				}
				break;
			case 'divi':
				$styles[] = get_template_directory_uri() . '/styles.css';
				break;
		}

		return array_filter($styles);
	}
}
