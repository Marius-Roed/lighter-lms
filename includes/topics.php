<?php

namespace LighterLMS;

use Exception;
use wpdb;

class Topics {

	/** @var wpdb */
	protected $db;

	/** @var string */
	public $table;

	public function __construct( $db = null ) {
		global $wpdb;
		$this->db    = $db ?: $wpdb;
		$this->table = $this->db->prefix . 'lighter_topics';
	}

	public function install() {
		$charset_collate = $this->db->get_charset_collate();

		$sql = "CREATE TABLE {$this->table} (
		  ID BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		  topic_key char(16) NOT NULL,
		  post_id bigint(20) UNSIGNED NOT NULL,
		  title varchar(255) NOT NULL,
		  sort_order INT UNSIGNED NOT NULL DEFAULT 1,
		  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		  PRIMARY KEY (ID),
		  UNIQUE KEY topic_key (topic_key),
		  KEY post_id (post_id),
		  KEY course_sort (post_id, sort_order),
		  FOREIGN KEY (post_id) REFERENCES {$this->db->posts}(ID) ON DELETE CASCADE ON UPDATE CASCADE
		) $charset_collate";

		require_once ABSPATH . '/wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	public function exists( $key ) {
		$sql = $this->db->prepare( "SELECT COUNT(*) FROM {$this->table} WHERE topic_key = %s", $key );

		$count = (int) $this->db->get_var( $sql );

		return $count > 0;
	}

	private function _parse_args( $args ) {
		$allowed = array( 'ID', 'post_id', 'topic_key', 'title', 'sort_order', 'created_at', 'updated_at' );
		$args    = wp_parse_args(
			$args,
			array(
				'per_page' => 20,
				'page'     => 1,
				'order'    => 'DESC',
				'order_by' => 'updated_at',
			)
		);

		$args['order_by'] = in_array( $args['order_by'], $allowed ) ? $args['order_by'] : 'updated_at';
		$args['order']    = strtoupper( $args['order'] ) === 'DESC' ? 'DESC' : 'ASC';
		$args['page']     = max( 1, intval( $args['page'] ) );
		$args['per_page'] = max( 0, intval( $args['per_page'] ) );

		return $args;
	}

	/**
	 * Save a new topic to the DB
	 *
	 * @param int $course_id ID of the course which the topic belongs to
	 * @param hex $key Optional. Unique identifier of the topic
	 * @param string $title Optional. The title of the topic
	 * @param int $sort_order Optional. The number the topic will be displayed when rendering. Default is 0.
	 *
	 * @return int|bool The ID of the new created row. False on failure.
	 */
	public function create( $course_id, $key = '', $title = 'New topic', $sort_order = 0 ) {
		$key = $this->_sanitize_key( $key );

		$title = sanitize_text_field( $title );

		$ins = $this->db->insert(
			$this->table,
			array(
				'post_id'    => $course_id,
				'topic_key'  => $key,
				'title'      => $title,
				'sort_order' => $sort_order,
			),
			array( '%d', '%s', '%s', '%d' )
		);

		if ( $ins !== false ) {
			return $this->db->insert_id;
		}

		return false;
	}

	public function get_by_course( $course_id ) {

		$post = get_post( $course_id );

		if ( $post->post_type !== lighter_lms()->course_post_type ) {
			_doing_it_wrong( __FUNCTION__, 'Can not call function on post with type ' . $post->post_type, '1.0.0' );
			return array();
		}

		return $this->db->get_results(
			$this->db->prepare(
				"SELECT * FROM {$this->table} WHERE post_id = %d ORDER BY sort_order ASC",
				$post->ID
			)
		);
	}

	public function get( $key, $args = array() ) {
		$args = $this->_parse_args( $args );

		if ( is_null( $key ) ) {
			$order_clause = $this->db->prepare( 'ORDER BY %s %s', $args['order_by'], $args['order'] );

			$limit_clause = '';
			if ( $args['per_page'] > 0 ) {
				$offset       = ( $args['page'] - 1 ) * $args['per_page'];
				$limit_clause = $this->db->prepare( 'LIMIT %d OFFSET %d', $args['per_page'], $offset );
			}

			$query = $this->db->prepare( "SELECT * FROM {$this->table} {$order_clause} {$limit_clause}" );

			$results = $this->db->get_results( $query );

			if ( $this->db->last_error ) {
				error_log( 'LighterLMS Query error: ' . $this->db->last_error . ' | Query: ' . $this->db->last_query );
				return false;
			}

			return $results ?: array();
		}

		if ( is_numeric( $key ) && (int) $key > 0 && fmod( $key, 1 ) === 0.0 ) {
			$result = $this->db->get_row(
				$this->db->prepare( "SELECT * FROM {$this->table} WHERE ID = %d", (int) $key )
			);
		} else {
			$result = $this->db->get_row(
				$this->db->prepare( "SELECT * FROM {$this->table} WHERE topic_key = %s", $key )
			);
		}

		if ( $this->db->last_error ) {
			error_log( "LighterLMS: Could not get result for {$key}. Query error: " . $this->db->last_error );
			return false;
		}

		return $result;
	}

	/**
	 * Update a topic
	 *
	 * @param string $key
	 * @param array $data
	 * @return bool|int Whether the update was successful. Int of the row, if a new row was inserted.
	 */
	public function update( $key, $data ) {
		if ( ! $this->exists( $key ) ) {
			return $this->create( $data['post_id'], $key, $data['title'], $data['sort_order'] );
		}

		$updated = $this->db->update(
			$this->table,
			$data,
			array( 'topic_key' => $key )
		);

		return $updated !== false;
	}

	public function delete( $key ) {
		$field  = is_int( $key ) ? 'ID' : 'topic_key';
		$format = is_int( $key ) ? '%d' : '%s';

		$deleted = $this->db->delete(
			$this->table,
			array( $field => $key ),
			array( $format )
		);

		return $deleted !== false;
	}

	public function search( $search_term, $args = array() ) {
		$args = $this->_parse_args( $args );

		$allowed_status = array( 'publish' );
		$offset         = ( $args['page'] - 1 ) * $args['per_page'];

		if ( current_user_can( 'edit_others_posts' ) ) {
			$allowed_status = array( 'publish', 'private', 'future', 'pending', 'draft', 'trash' );
		} elseif ( current_user_can( 'read_private_posts' ) ) {
			$allowed_status[] = 'private';
		}

		$order_clause = $this->db->prepare( 'ORDER BY %s %s', $args['order_by'], $args['order'] );

		$limit_clause = '';
		if ( $args['per_page'] > 0 ) {
			$offset       = ( $args['page'] - 1 ) * $args['per_page'];
			$limit_clause = $this->db->prepare( 'LIMIT %d OFFSET %d', $args['per_page'], $offset );
		}

		$status = implode( "','", $allowed_status );

		$union = $this->db->prepare(
			"SELECT 
				{$this->db->posts}.post_title,
				{$this->table}.post_id,
				GROUP_CONCAT(
					CONCAT({$this->table}.topic_key, '^', {$this->table}.title)
				SEPARATOR '\x1F'
				) AS topics,
				COUNT(*) AS topic_count
			FROM {$this->db->posts}
			INNER JOIN {$this->table} ON {$this->db->posts}.ID = {$this->table}.post_id
			WHERE {$this->db->posts}.post_title LIKE '%%%s%%'
				AND {$this->db->posts}.post_status IN ('{$status}')
			GROUP BY {$this->db->posts}.ID, {$this->db->posts}.post_title
			{$limit_clause}",
			$search_term
		);

		$res = $this->db->get_results( $union, ARRAY_A );

		if ( $this->db->last_error ) {
			error_log( 'LighterLMS Query Error: ' . $this->db->last_error );
			return false;
		}

		$res = array_map(
			fn( $topic ) => array(
				'title'  => $topic['post_title'],
				'ID'     => $topic['post_id'],
				'topics' => array_map( fn( $val ) => array( explode( '^', $val )[0] => explode( '^', $val )[1] ), explode( "\x1F", $topic['topics'] ) ),
				'count'  => $topic['topic_count'],
			),
			$res
		);

		$total = array_sum( array_column( $res, 'count' ) );

		return array( $res, $total );
	}

	public function get_next_sort_order( int $course_id ): int {
		$max = $this->db->get_var(
			$this->db->prepare(
				"SELECT MAX(sort_order) FROM {$this->table} WHERE course_id = %d",
				$course_id,
			)
		);

		return $max !== null ? (int) $max + 1 : 1;
	}

	private function _sanitize_key( $key ) {
		if ( ! $key ) {
			return Randflake::generate();
		}

		$key_len = strlen( $key );

		if ( ! ctype_alnum( $key ) || $key_len < 11 || $key_len > 13 ) {
			return Randflake::generate();
		}

		$id = base_convert( $key, 36, 10 );
		if ( $id === false ) {
			return Randflake::generate();
		}

		$reencoded = base_convert( $id, 10, 36 );
		if ( strtolower( $key ) !== $reencoded ) {
			return Randflake::generate();
		}

		return strtolower( $key );
	}

	public static function normalise_for_rest( $topic ) {
		return array(
			'id'        => (int) $topic->ID,
			'key'       => $topic->topic_key,
			'course'    => (int) $topic->post_id,
			'title'     => $topic->title,
			'sortOrder' => (int) $topic->sort_order,
			'updated'   => $topic->updated_at,
			'created'   => $topic->created_at,
		);
	}
}
