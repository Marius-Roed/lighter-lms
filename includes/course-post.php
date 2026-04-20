<?php

namespace LighterLMS;

defined( 'ABSPATH' ) || exit;

use LighterLMS\DB\Topics_Controller;
use LighterLMS\Traits\Lighter_LMS_Hooks;
use WP_Post;

/**
 * @extends Post_Type<mixed, mixed>
 */
class Course_Post extends Post_Type {

	public function __construct() {
		parent::__construct( lighter_lms()->course_post_type );

		add_action( 'admin_post_lighter_lms_add_topic', array( $this, 'add_topic' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );
		add_action( 'template_redirect', array( $this, 'course_access' ) );

		add_action(
			'plugins_loaded',
			function () {
				$prio = 10;

				if ( lighter_lms()->is_theme( 'breackdance-zero-theme-master' ) ) {
					$prio = 1000010;
				}

				add_filter( 'template_include', array( $this, 'course_template' ), $prio );
			}
		);
	}

	/**
	 * Register Course post type
	 */
	public function register(): void {
		$labels = array(
			'name'               => _x( 'Courses', 'post type plural name', 'lighterlms' ),
			'singular_name'      => _x( 'Course', 'post type singular name', 'lighterlms' ),
			'menu_name'          => _x( 'Courses', 'admin menu', 'lighterlms' ),
			'name_admin_bar'     => _x( 'Course', 'add new on admin bar', 'lighterlms' ),
			'add_new'            => _x( 'Add New', 'add lighterlms course', 'lighterlms' ),
			'add_new_item'       => __( 'Add New Course', 'lighterlms' ),
			'new_item'           => __( 'New Course', 'lighterlms' ),
			'edit_item'          => __( 'Edit Course', 'lighterlms' ),
			'view_item'          => __( 'View Course', 'lighterlms' ),
			'all_items'          => __( 'Courses', 'lighterlms' ),
			'search_items'       => __( 'Search Courses', 'lighterlms' ),
			'parent_item_colon'  => __( 'Parent Courses:', 'lighterlms' ),
			'not_found'          => __( 'No courses found.', 'lighterlms' ),
			'not_found_in_trash' => __( 'No courses found in Trash.', 'lighterlms' ),
		);

		$args = array(
			'labels'                => $labels,
			'description'           => __( 'Course description.', 'lighterlms' ),
			'public'                => true,
			'publicly_queryable'    => true,
			'query_var'             => true,
			'rewrite'               => array(
				'slug'       => 'kurser',
				'with_front' => true,
			),
			'menu_icon'             => 'dashicons-book-alt',
			'capability_type'       => 'post',
			'has_archive'           => true,
			'hierarchical'          => false,
			'supports'              => array( 'custom-fields' ),
			'show_in_menu'          => lighter_lms()->admin_url,
			'show_in_rest'          => true,
			'rest_controller_class' => 'WP_REST_Posts_Controller',
			'rest_base'             => 'lighter_courses',
			'register_meta_box_cb'  => array( $this, 'register_meta_boxes' ),
		);

		register_post_type( $this->post_type, $args );

		$this->register_tags( 'Course' );
	}

	/**
	 * Save post content
	 *
	 * @param int      $post_id    The post ID.
	 * @param \WP_Post $post       The post object.
	 */
	public function save_post( int $post_id, \WP_Post $post ): void {
		$nonce = $_POST['lighter_nonce'] ?? '';
		if ( ! $this->verify_nonce( $post, $nonce, $this->post_type . '_fields' ) ) {
			return;
		}

		file_put_contents( LIGHTER_LMS_PATH . '/log.log', var_export( $_POST, true ) );
		return;

		if ( isset( $_POST['topics'] ) ) {
			foreach ( $_POST['topics'] as $topic ) {
				$data = array(
					'course_id'  => $post_id,
					'title'      => $topic['title'],
					'sort_order' => $topic['sort_order'],
				);
				lighter()->lms->db->topics->update( $topic['key'], $data );

				if ( isset( $topic['lessons'] ) ) {
					foreach ( $topic['lessons'] as $lesson ) {
						if ( $lesson['dirty'] ) {
							Lesson_Post::save_from_course( $lesson, $post_id, $topic );
						}
					}
				}
			}
		}

		$settings = $_POST['settings'] ?? array();

		if ( ! empty( $settings ) ) {
			$this->_save_settings( $post, $settings );
		}
	}

	/**
	 * Save course settings.
	 *
	 * @param \WP_Post $post The post object.
	 * @param array    $args The settings to save.
	 */
	protected function _save_settings( \WP_Post $post, array $args ): void {
		$tags          = $args['tags'] ?? array();
		$product       = $args['product'] ?? array();
		$header        = isset( $args['display_header'] ) ? wp_validate_boolean( $args['display_header'] ) : lighter_lms()->defaults()->course_hide_theme_header;
		$footer        = isset( $args['display_footer'] ) ? wp_validate_boolean( $args['display_footer'] ) : lighter_lms()->defaults()->course_hide_theme_footer;
		$sidebar       = isset( $args['display_sidebar'] ) ? wp_validate_boolean( $args['display_sidebar'] ) : lighter_lms()->defaults()->course_hide_theme_sidebar;
		$sync_prod_img = isset( $args['sync_prod_img'] ) ? wp_validate_boolean( $args['sync_prod_img'] ) : true;
		$slug          = $args['slug'] ? sanitize_post_field( 'post_name', $args['slug'], $post->ID, 'raw' ) : $post->post_name;
		$thumbnail     = $args['thumbnail'];

		if ( ! empty( $tags ) ) {
			wp_set_post_terms( $post->ID, $tags, 'course-tags' );
		}

		if ( ! empty( $product ) ) {
			$product['slug'] = $slug;
			$product['tags'] = $tags;
			$saved_prod      = lighter_save_product( $product, $post->ID );

			$img_id = $product['images'][0]['id'] ?? false;
			if ( $img_id && $sync_prod_img ) {
				set_post_thumbnail( $post->ID, $img_id );
			}

			update_post_meta( $post->ID, '_lighter_is_restricted', true );
		}

		update_post_meta( $post->ID, '_course_display_theme_header', $header );
		update_post_meta( $post->ID, '_course_display_theme_sidebar', $sidebar );
		update_post_meta( $post->ID, '_course_display_theme_footer', $footer );
		if ( $thumbnail && ! $sync_prod_img ) {
			set_post_thumbnail( $post->ID, $thumbnail['id'] );
		}

		file_put_contents( LIGHTER_LMS_PATH . '/log.log', var_export( $slug, true ), FILE_APPEND );

		if ( $post->post_name !== $slug && ! $this->skip_next_save ) {
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

	public function delete_post( int $post_id, WP_Post $post ): void {
		throw new \Exception( 'Not implemented' );
	}

	/**
	 * Check user access to course.
	 */
	public function course_access(): void {
		if ( ! is_singular( $this->post_type ) ) {
			return;
		}

		global $post;
		$is_restricted = get_post_meta( $post->ID, '_lighter_is_restricted', true );
		$is_restricted = wp_validate_boolean( $is_restricted );
		if ( $is_restricted ) {
			$user_access = new User_Access();
			if ( ! $user_access->check_course_access( $post->ID ) ) {
				// TODO: Show access denied template.
				wp_die( 'access denied', __( 'Please purchase the course or log in to view.', 'lighterlms' ), array( 'response' => 403 ) );
			}
		}
	}

	/**
	 * Course template loader.
	 *
	 * @param string $template Current template
	 * @return string
	 */
	public function course_template( string $template ): string {
		if ( ! is_singular( $this->post_type ) ) {
			return $template;
		}

		$new = null; // TODO: locate_template(lighter_lms()->course_template);

		if ( ! empty( $new ) ) {
			return $new;
		}

		return lighter_lms()->standard_template;
	}

	/**
	 * Course admin list view columns
	 *
	 * @param string[] $columns Existing columns.
	 * @return string[] The newly set columns.
	 */
	public function columns( array $columns ): array {
		$date = isset( $columns['date'] ) ? $columns['date'] : false;

		if ( $date ) {
			unset( $columns['date'] );
		}

		$columns['tags'] = __( 'Tags' );

		if ( $date ) {
			$columns['date'] = $date;
		}

		return $columns;
	}

	/**
	 * Course custom columns content
	 *
	 * @param string $column     The column name
	 * @param int    $post_id    The post ID.
	 */
	public function custom_columns( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'tags':
				$tags = get_the_term_list( $post_id, 'course-tags', '', ', ' );
				if ( $tags ) {
					echo wp_kses_post( $tags );
				}
				break;
		}
	}

	/**
	 * Course REST query modifier
	 *
	 * @param array            $args   The query args.
	 * @param \WP_REST_Request $req    The request object.
	 *
	 * @return array
	 */
	public function rest_query( array $args, \WP_REST_Request $req ): array {
		$status_param = $req->get_param( 'filter_status' );
		$valid_stati  = array();
		if ( ! empty( $status_param ) && is_string( $status_param ) ) {
			$statuses = explode( ',', $status_param );
			foreach ( $statuses as $status ) {
				$status = trim( $status );
				if ( get_post_status_object( $status ) ) {
					$valid_stati[] = $status;
				}
			}
		}

		if ( empty( $valid_stati ) ) {
			$valid_stati = array( 'publish', 'draft', 'future', 'private', 'auto-draft', 'pending' );
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
	public function register_meta_boxes(): void {
		add_meta_box(
			'coursecontentdiv',
			__( 'Course content', 'lighterlms' ),
			array( $this, 'details' ),
			$this->post_type,
			'normal',
			'high'
		);

		add_meta_box(
			'coursesettingsdiv',
			__( 'Course settings', 'lighterlms' ),
			array( $this, 'settings' ),
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
	public function details( \WP_Post $post ): void {
		lighter_view(
			'course-modules',
			array(
				'admin' => true,
				'post'  => $post,
			)
		);
	}

	/**
	 * Course settings metabox content.
	 *
	 * @param \WP_Post $post The post object.
	 */
	public function settings( \WP_Post $post ): void {
		lighter_view(
			'course-settings',
			array(
				'admin' => true,
				'post'  => $post,
			)
		);
	}

	/**
	 * Add a new topic via. form submission.
	 */
	public function add_topic(): void {
		$course_id = (int) $_POST['post_ID'];

		lighter()->lms->topic->create( $course_id, 'New topic' );

		wp_redirect( wp_get_referer() );
		exit;
	}

	/**
	 * Enqueue needed scripts.
	 */
	public function scripts() {
		if ( is_singular( $this->post_type ) ) {
			wp_enqueue_style( 'lighter_lms_frontend' );

			wp_enqueue_script( 'lighter_lms_course_js' );
			wp_enqueue_script( 'wp-api-fetch' );
		}
	}

	protected static function _generate_object(): array {
		$post = get_post();

		$topics = lighter()->lms->course->get_topics( $post->ID );

		if ( ! empty( $topics ) ) {
			$topics = array_map(
				fn( $t ) => lighter()->lms->topic::normalise_for_rest( $t, true ),
				$topics
			);
		}

		return array(
			'id'           => $post->ID,
			'slug'         => $post->post_name,
			'status'       => $post->post_status,
			'type'         => 'course',
			'title'        => array(
				'rendered' => get_the_title( $post ),
				'raw'      => $post->post_title,
			), // Use get_the_title to go through all filters
			'content'      => array(
				'rendered' => get_the_content( post: $post ),
				'raw'      => $post->post_content,
			),
			'excerpt'      => array(
				'rendered' => get_the_excerpt( $post ),
				'raw'      => $post->post_excerpt,
			),
			'author'       => (int) $post->post_author,
			'date'         => $post->post_date,
			'date_gmt'     => $post->post_date_gmt,
			'modified'     => $post->post_modified,
			'modified_gmt' => $post->post_modified_gmt,
			'menu_order'   => $post->menu_order,
			'parent'       => 0,
			'meta'         => (object) array(),
			'course_key'   => '',
			'topics'       => $topics,
		);
	}
}
