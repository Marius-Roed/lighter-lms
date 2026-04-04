<?php

namespace LighterLMS;

defined( 'ABSPATH' ) || exit;

/**
 * @template TVal
 * @template TKey
 */
abstract class Post_Type {

	public string $post_type;
	protected bool $skip_next_save            = false;
	protected static bool $shared_hooks_added = false;

	public function __construct( $post_type_slug ) {
		$this->post_type = $post_type_slug;

		if ( ! post_type_exists( $this->post_type ) ) {
			add_action( 'init', array( $this, 'register' ) );
		}

		add_action( 'save_post_' . $this->post_type, array( $this, 'save_post' ), 10, 2 );
		add_action( 'manage_' . $this->post_type . '_posts_custom_column', array( $this, 'custom_columns' ), 10, 2 );
		add_action( 'delete_post_' . $this->post_type, array( $this, 'delete_post' ), 10, 2 );

		add_filter( 'manage_' . $this->post_type . '_posts_columns', array( $this, 'columns' ) );
		add_filter( 'rest_' . $this->post_type . '_query', array( $this, 'rest_query' ), 20, 2 );
		add_filter( 'get_user_option_screen_layout_' . $this->post_type, array( $this, 'screen_layout' ) );

		if ( ! self::$shared_hooks_added ) {
			add_action( 'do_meta_boxes', array( __CLASS__, 'remove_submitdiv' ) );
			add_action( 'edit_form_after_title', array( __CLASS__, 'no_script' ) );

			add_filter( 'post_class', array( __CLASS__, 'post_class' ), 10, 3 );
			add_filter( 'lighter_lms_admin_object', array( __CLASS__, 'js_objects' ), 10, 2 );
			self::$shared_hooks_added = true;
		}
	}

	/** Registers the post type (override in child class) */
	abstract public function register(): void;

	protected function register_tags( string $name ): void {
		$name = strpos( '-tags', $name ) == false ? strtolower( $name ) . '-tags' : strtolower( $name );

		$labels = array(
			'name'                       => _x( 'Tags', 'taxonomy general name' ),
			'singular_name'              => _x( 'Tag', 'taxonomy singular name' ),
			'search_items'               => __( 'Search Tags' ),
			'popular_items'              => __( 'Popular Tags' ),
			'all_items'                  => __( 'All Tags' ),
			'parent_item'                => null,
			'parent_item_colon'          => null,
			'edit_item'                  => __( 'Edit Tag' ),
			'update_item'                => __( 'Update Tag' ),
			'add_new_item'               => __( 'Add new Tag' ),
			'new_item_name'              => __( 'New Tag name' ),
			'separate_items_with_commas' => __( 'Separate Tags with commas' ),
			'add_or_remove_items'        => __( 'Add or remove Tags' ),
			'choose_from_most_used'      => __( 'Choose from most used Tags' ),
			'menu_name'                  => __( 'Tags' ),
		);

		register_taxonomy(
			$name,
			$this->post_type,
			array(
				'hierarchical'          => false,
				'labels'                => $labels,
				'show_ui'               => false,
				'show_in_rest'          => true,
				'update_count_callback' => '_update_post_term_count',
				'query_var'             => true,
				'rewrite'               => array( 'slug' => $name ),
			)
		);
	}

	public function verify_nonce( \WP_Post $post, string $nonce, string $action = '' ): bool {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		if ( $post->post_type !== $this->post_type ) {
			return false;
		}

		if ( wp_is_post_revision( $post ) || ! wp_verify_nonce( $nonce, $action ) ) {
			return false;
		}

		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return false;
		}

		return ! $this->skip_next_save;
	}

	/** Saves the post data (override in child class) */
	abstract public function save_post( int $post_id, \WP_Post $post ): void;

	/** Deletes necessary meta just before it is deleted from the database (override in child class) */
	abstract public function delete_post( int $post_id, \WP_Post $post ): void;

	/** Save the post settings (override in child class) */
	abstract protected function _save_settings( \WP_Post $post, array $args ): void;

	/** Registers the post meta boxes (override in child class - default empty) */
	public function register_meta_boxes(): void {}

	/**
	 * Custom columns for admin list view (override in child class - default none)
	 *
	 * @param string    $column     The column name.
	 * @param int       $post_id    The post ID.
	 */
	public function custom_columns( string $column, int $post_id ): void {}

	/**
	 * Modifies admin list view columns (override in child class)
	 *
	 * @param string[] $columns The names of columns
	 * @return string[]
	 */
	public function columns( array $columns ): array {
		return $columns;
	}

	/**
	 * Modifies REST args (override in child class)
	 *
	 * @param array             $args   The query args.
	 * @param \WP_REST_Request  $req    The request object.
	 * @return array
	 */
	public function rest_query( array $args, \WP_REST_Request $req ): array {
		return $args;
	}

	/**
	 * Screen layout filter (force single column)
	 *
	 * @param int $columns Current columns layout.
	 * @return int
	 */
	public function screen_layout( int $columns = 0 ): int {
		return 1;
	}

	/** Remove submit and slug metaboxes */
	public static function remove_submitdiv(): void {
		$course_post_type = lighter_lms()->course_post_type;
		$lesson_post_type = lighter_lms()->lesson_post_type;

		remove_meta_box( 'submitdiv', $course_post_type, 'side' );
		remove_meta_box( 'slugdiv', $course_post_type, 'normal' );
		remove_meta_box( 'submitdiv', $lesson_post_type, 'side' );
		remove_meta_box( 'slugdiv', $lesson_post_type, 'normal' );
	}

	/**
	 * Modifies post element classes.
	 *
	 * @param string[] $classes An array of post class names.
	 * @param string[] $class An array of additional class names added to the post.
	 * @param int $post_id The post ID.
	 * @string[]
	 */
	public static function post_class( array $classes, array $class, int $post_id ): array {
		if ( ! is_admin() ) {
			return $classes;
		}

		$screen = get_current_screen();

		if ( 'edit' !== $screen->base && in_array( $screen->post_type, lighter_lms()->post_types ) ) {
			return $classes;
		}

		$classes[] = 'lighter-post';

		return $classes;
	}

	/**
	 * Generate the post's js object (override in child class)
	 *
	 * @return array<TKey, TVal>
	 */
	protected static function _generate_object(): array {
		throw new \BadMethodCallException(
			'Child must override ' . static::class . '::_generate_object()'
		);
	}

	/**
	 * Output JS disabled warning
	 *
	 * @param \WP_Post $post The post object.
	 */
	public static function no_script( \WP_Post $post ): void {
		if ( ! in_array( $post->post_type, lighter_lms()->post_types ) ) {
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

	/**
	 * Add JS objects to admin
	 *
	 * @param array $obj Existing object
	 * @param string $screen_id The current screen ID.
	 * @return array
	 */
	final public static function js_objects( array $obj, string $screen_id ): array {
		if ( 'lighter_lessons' === $screen_id ) {
			$obj['lesson']['settings'] = lighter_get_lesson_settings();
		}

		if ( 'lighter_courses' === $screen_id ) {
			$obj['course']             = Course_Post::_generate_object();
			$obj['course']['settings'] = lighter_get_course_settings();
		}

		return $obj;
	}
}
