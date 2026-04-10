<?php

namespace LighterLMS;

use WP_Post;

defined( 'ABSPATH' ) || exit;

class Lesson_Post extends Post_Type {

	public function __construct() {
		parent::__construct( lighter_lms()->lesson_post_type );
	}

	/**
	 * Register lesson post type.
	 */
	public function register(): void {
		$labels = array(
			'name'               => _x( 'Lessons', 'post type plural name', 'lighterlms' ),
			'singular_name'      => _x( 'Lesson', 'post type singular name', 'lighterlms' ),
			'menu_name'          => _x( 'Lessons', 'admin menu', 'lighterlms' ),
			'name_admin_bar'     => _x( 'Lesson', 'add new on admin bar', 'lighterlms' ),
			'add_new'            => _x( 'Add New', 'add lighterlms course', 'lighterlms' ),
			'add_new_item'       => __( 'Add New Lesson', 'lighterlms' ),
			'new_item'           => __( 'New Lesson', 'lighterlms' ),
			'edit_item'          => __( 'Edit Lesson', 'lighterlms' ),
			'view_item'          => __( 'View Lesson', 'lighterlms' ),
			'all_items'          => __( 'Lessons', 'lighterlms' ),
			'search_items'       => __( 'Search Lessons', 'lighterlms' ),
			'parent_item_colon'  => __( 'Parent Lessons:', 'lighterlms' ),
			'not_found'          => __( 'No lessons found.', 'lighterlms' ),
			'not_found_in_trash' => __( 'No lessons found in Trash.', 'lighterlms' ),
		);

		$args = array(
			'labels'               => $labels,
			'description'          => __( 'Lesson description.', 'lighterlms' ),
			'public'               => true,
			'publicly_queryable'   => true,
			'show_ui'              => true,
			'show_in_rest'         => true,
			'show_in_menu'         => lighter_lms()->admin_url,
			'query_var'            => true,
			'rewrite'              => array( 'slug' => 'lektioner' ),
			'menu_icon'            => 'dashicons-list-view',
			'capability_type'      => 'post',
			'has_archive'          => false,
			'hierarchical'         => false,
			'menu_position'        => null,
			'supports'             => array( 'editor', 'custom_fields' ),
			'exclude_from_search'  => true,
			'register_meta_box_cb' => array( $this, 'register_meta_boxes' ),
		);

		register_post_type( $this->post_type, $args );
		$this->register_rest_fields();

		$this->_handle_third_party_support();
	}

	/**
	 * Handle support for third party plugins.
	 */
	private function _handle_third_party_support(): void {
		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		add_post_type_support( $this->post_type, 'elementor' );

		register_post_meta(
			$this->post_type,
			'_elementor_data',
			array(
				'type'          => 'string',
				'description'   => 'Elementor layout data',
				'single'        => true,
				'show_in_rest'  => true,
				'schema'        => array(
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'required'    => false,
					'arg_options' => array(
						'sanitize_callback' => null,
					),
				),
				'auth_callback' => function ( $attr, $request, $meta_key ) {
					return current_user_can( 'edit_post', $request['id'] ?? 0 );
				},
			)
		);

		$elementor_meta_keys = array(
			'_elementor_page_settings',
			'_elementor_css',
			'_elementor_version',
			'_elementor_edit_mode',
			'_elementor_controls_usage',
		);
		foreach ( $elementor_meta_keys as $key ) {
			register_post_meta(
				$this->post_type,
				$key,
				array(
					'type'         => 'object',
					'description'  => $key . ' for Elementor',
					'single'       => true,
					'show_in_rest' => true,
					'schema'       => array(
						'type'    => 'object',
						'context' => array( 'view', 'edit' ),
					),
				)
			);
		}
	}

	public function register_rest_fields(): void {
		$fields = array(
			'lesson_key'  => array(
				'type'     => 'string',
				'sanitize' => 'sanitize_text_field',
			),
			'lesson_type' => array(
				'type'     => 'string',
				'sanitize' => 'sanitize_text_field',
			),
		);

		foreach ( $fields as $name => $config ) {
			register_rest_field(
				$this->post_type,
				'lighter_' . $name,
				array(
					'get_callback'    => fn( $post ) => get_post_meta( $post['id'], '_lighter_' . $name, true ),
					'update_callback' => fn( $value, $post ) => update_post_meta(
						$post->ID,
						'_lighter_' . $name,
						( $config['sanitize'] )( $value ),
					),
					'schema'          => array(
						'type'    => $config['type'],
						'context' => array( 'view', 'edit' ),
					),
				)
			);
		}

		register_rest_field(
			$this->post_type,
			'_lighter_meta',
			array(
				'get_callback'    => fn( $post ) => $this->get_lesson_meta( (int) $post['id'] ),
				'update_callback' => fn( $value, $object ) => $this->update_lesson_meta( $object, $value ),
			)
		);
	}

	/**
	 * Save lesson content
	 *
	 * @param int      $post_id    The post ID.
	 * @param \WP_Post $post       The post object.
	 */
	public function save_post( int $post_id, \WP_Post $post ): void {
		$nonce = $_POST['lighter_nonce'] ?? '';
		if ( ! $this->verify_nonce( $post, $nonce, $this->post_type . '_fields' ) ) {
			return;
		}

		$settings = $_POST['settings'] ?? array();

		$this->_save_settings( $post, $settings );
	}

	/**
	 * Save lesson settings.
	 *
	 * @param \WP_Post $post The post object.
	 * @param array    $args The settings to save.
	 */
	protected function _save_settings( \WP_Post $post, array $args ): void {
		$parents = $args['parents'];

		foreach ( $parents as $parent ) {
			$topic = lighter()->lms->topic->get( $parent );
			if ( ! $topic ) {
				continue;
			}
			lighter()->lms->lesson->create_topic_relationship(
				array(
					'lesson_id' => $post->ID,
					'topic_id'  => $topic->ID,
				)
			);
		}

		if ( isset( $args['slug'] ) && ! empty( $args['slug'] ) ) {
			$slug = sanitize_title( $args['slug'] );
			if ( $slug !== get_post_field( 'post_name', $post, 'raw' ) ) {
				$this->skip_next_save = true;
				wp_update_post(
					array(
						'ID'        => $post->ID,
						'post_name' => $slug,
					)
				);
				$this->skip_next_save = false;
			}
		}

		return;
	}

	public function delete_post( int $post_id, WP_Post $post ): void {
		lighter()->lms->lesson->delete_topic_relationship();
	}

	/**
	 * Register lesson metaboxes.
	 */
	public function register_meta_boxes(): void {
		add_meta_box(
			'lessonsettingsdiv',
			__( 'Lesson settings', 'lighterlms' ),
			array( $this, 'render_settings' ),
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
	public function render_settings( \WP_Post $post ): void {
		lighter_view(
			'lesson-settings',
			array(
				'admin' => true,
				'post'  => $post,
			)
		);
	}

	public function get_lesson_meta( \WP_Post|int $post ): array {
		$post = get_post( $post );
		$meta = array();

		$topics = lighter()->lms->lesson->get_parent_topics( $post );

		foreach ( $topics as $topic ) {
			if ( ! isset( $meta[ $topic->course_id ] ) ) {
				$meta[ $topic->course_id ] = array(
					'course_id' => $topic->course_id,
					'title'     => get_the_title( $topic->course_id ),
				);
			}

			$meta[ $topic->course_id ]['topics'][] = lighter()->lms->lesson->get_topic_data( $post, $topic );
		}

		return $meta;
	}

	public function update_lesson_meta( \WP_Post|int $post, mixed $new_value ): void {
		foreach ( $new_value as $course_id => $course ) {
			if ( $course_id !== $course['course_id'] ) {
				continue;
			}

			$course_post = get_post( $course_id );

			if ( $course_post->post_type !== lighter_lms()->course_post_type || empty( $course['topics'] ) ) {
				continue;
			}

			foreach ( $course['topics'] as $topic ) {
				$exists = lighter()->lms->topic->get( $topic['key'] );
				if ( ! $exists ) {
					continue;
				}

				lighter()->lms->lesson->update_topic_relationship(
					array(
						'topic_id'   => $exists->ID,
						'lesson_id'  => $post->ID,
						'sort_order' => $topic['sort_order'],
					)
				);
			}
		}
	}

	/**
	 * Save lesson
	 *
	 * @param array  $args the lesson arguments.
	 * @param int    $parent_id The ID of the parent saving the lesson.
	 * @param array  $topic The topic data.
	 * @param Topics $topic_db
	 *
	 * @return int The saved lesson ID.
	 */
	public static function save_from_course( array $args, int $parent_id, array $topic ): int {
		$insert_args = array(
			'post_title'  => $args['title'],
			'post_status' => $args['postStatus'],
			'post_type'   => lighter_lms()->lesson_post_type,
			'meta_input'  => array(
				'_lighter_sort_order'   => $args['sortOrder'],
				'_lighter_parent_topic' => $topic['key'],
				'_lighter_lesson_key'   => $args['key'],
			),
		);

		if ( $args['id'] ) {
			$insert_args['ID'] = $args['id'];
		}

		if ( isset( $insert_args['ID'] ) ) {
			$inserted = wp_update_post( $insert_args );
		} else {
			$inserted = wp_insert_post( $insert_args );
		}

		if ( $inserted ) {
			$t      = lighter()->lms->topic->get( $topic['key'] );
			$l_args = array(
				'lesson' => $inserted,
				'parent' => $parent_id,
				'topic'  => $t->ID,
			);
			$less   = new Lessons( $l_args );
			$less->save();
		}

		return $inserted;
	}

	protected static function _generate_object(): array {
		throw new \Exception( 'Not implemented' );
	}
}
