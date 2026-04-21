<?php

namespace LighterLMS\DB;

defined( 'ABSPATH' ) || exit;

use wpdb;
use LighterLMS\Randflake;

readonly class TopicRow {
	public function __construct(
		public int $ID,
		public string $topic_key,
		public string $title,
		public int $course_id,
		public int $sort_order,
		public string $updated_at,
		public string $created_at,
	) {}
}

class Topics_Repository {

	const ALLOWED_STATUSES = array(
		'publish',
		'trash',
		'draft',
		'future',
		'pending',
	);

	const CACHE_GROUP = 'lighter_lms_topics';

	protected \wpdb $db;
	public string $table;

	public function __construct( \wpdb|null $db = null ) {
		global $wpdb;
		$this->db    = $db ?? $wpdb;
		$this->table = $this->db->prefix . lighter_lms()->topics_table;
	}

	public function find( int|string $id ): ?TopicRow {
		$c_key  = $this->_cache_key_id( $id );
		$cached = wp_cache_get( $c_key, self::CACHE_GROUP );

		if ( $cached !== false ) {
			return $cached ?: null;
		}

		$identifier = $this->_resolve_identifier( $id );
		$format     = $identifier['column'] === 'ID' ? '%d' : '%s';

		$row = $this->db->get_row(
			$this->db->prepare(
				"SELECT * FROM {$this->table}
                WHERE {$identifier['column']} = {$format}",
				$identifier['value']
			)
		);

		$topic = $row ? new TopicRow( ...(array) $row ) : null;

		// NOTE: Cache both id and key so either hits lookup hits
		if ( $topic ) {
			wp_cache_set( 'topic:' . $topic->ID, $topic, self::CACHE_GROUP, HOUR_IN_SECONDS );
			wp_cache_set( 'topic:' . $topic->topic_key, $topic, self::CACHE_GROUP, HOUR_IN_SECONDS );
		} else {
			wp_cache_set( $c_key, 0, self::CACHE_GROUP, HOUR_IN_SECONDS );
		}

		return $topic;
	}

	/**
	 * @return TopicRow[]
	 */
	public function find_by_course( int $course_id ): array {
		$c_key  = $this->_cache_key_course( $course_id );
		$cached = wp_cache_get( $c_key, self::CACHE_GROUP );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		$results = $this->db->get_results(
			$this->db->prepare(
				"SELECT * FROM {$this->table}
                WHERE course_id = %d
                ORDER BY sort_order ASC",
				$course_id
			)
		);

		$topics = array_map( fn( $row ) => new TopicRow( ...(array) $row ), $results );

		wp_cache_set( $c_key, $topics, self::CACHE_GROUP, HOUR_IN_SECONDS );

		return $topics;
	}

	/**
	 * @param array{topic_key: string, course_id: int, title: string, sort_order: int} $data
	 *
	 * @throws \RuntimeException
	 */
	public function insert( array $data ): int {
		$topic_key = isset( $data['topic_key'] ) && Randflake::validate( $data['topic_key'] )
			? $data['topic_key']
			: Randflake::generate();

		$inserted = $this->db->insert(
			$this->table,
			array(
				'topic_key'  => $topic_key,
				'course_id'  => (int) $data['course_id'],
				'title'      => sanitize_text_field( $data['title'] ),
				'sort_order' => (int) $data['sort_order'],
			),
			array()
		);

		if ( $inserted === false ) {
			throw new \RuntimeException( 'Failed to insert topic: ' . $this->db->last_error );
		}

		return $this->db->insert_id;
	}

	/**
	 * @param array{course_id?: int, title?: string, sort_order?: int} $data
	 *
	 * @throws \RuntimeException
	 */
	public function update( int|string $id, array $data ): void {
		$sanitizers = array(
			'course_id'  => 'intval',
			'title'      => 'sanitize_text_field',
			'sort_order' => 'intval',
		);
		$cleaned    = array();

		foreach ( $sanitizers as $column => $sanitize ) {
			if ( array_key_exists( $column, $data ) ) {
				$cleaned[ $column ] = $sanitize( $data[ $column ] );
			}
		}

		if ( empty( $cleaned ) ) {
			return;
		}

		$existing = $this->find( $id );
		if ( ! $existing ) {
			throw new \RuntimeException( "Cannot update topic with unknown identifier \"$id\"." );
		}

		$identifier = $this->_resolve_identifier( $id );

		$updated = $this->db->update(
			$this->table,
			$cleaned,
			array( $identifier['column'] => $identifier['value'] )
		);

		if ( $updated === false ) {
			throw new \RuntimeException( "Failed to update topic {$id}: " . $this->db->last_error );
		}

		$this->_invalidate_topic_cache( $existing );

		if ( isset( $cleaned['course_id'] ) && $cleaned['course_id'] !== $existing->course_id ) {
			wp_cache_delete( $this->_cache_key_course( $cleaned['course_id'] ), self::CACHE_GROUP );
		}
	}

	/**
	 * @throws \RuntimeException
	 */
	public function delete( int $id ): void {
		$topic = $this->find( $id );

		if ( ! $topic ) {
			throw new \RuntimeException( "Cannot delete topic with ID \"$id\". Not found" );
		}

		$deleted = $this->db->delete(
			$this->table,
			array( 'ID' => $id ),
			array( '%d' )
		);

		if ( $deleted === false ) {
			throw new \RuntimeException( "Failed to delete topic {$id}: " . $this->db->last_error );
		}

		$this->_invalidate_topic_cache( $topic );
	}

	/**
	 * @param string|string[] $status
	 *
	 * @return object[]
	 */
	public function search( string $query, string|array $status = 'publish', int $limit = 5 ): array {
		$statuses = $this->_normalise_statuses( $status );
		$like     = '%' . $this->db->esc_like( $query ) . '%';

		$status_placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );

		$params = array_merge( array( $like, $like, lighter_lms()->course_post_type ), $statuses, array( $like, $like, $limit ) );

		$sql = $this->db->prepare(
			"SELECT
				t.ID,
				t.topic_key,
				t.title,
				t.sort_order,
				p.ID         AS course_id,
				CASE
					WHEN t.title      LIKE %s THEN 'topic'
					WHEN p.post_title LIKE %s THEN 'course'
				END AS match_type
			FROM {$this->table} t
			JOIN {$this->db->posts} p
				ON p.ID           = t.course_id
				AND p.post_type   = %s
				AND p.post_status IN ({$status_placeholders})
            WHERE t.title LIKE %s
                OR p.post_title LIKE %s
            ORDER BY p.post_title ASC, t.sort_order ASC
			LIMIT %d",
			...$params
		);

		return $this->db->get_results( $sql );
	}

	/**
	 * @param array<int|string, int> $sort_orders [ topic_id => sort_order, ... ]
	 *
	 * @throws \RuntimeException
	 */
	public function bulk_update_sort_order( array $sort_orders ): void {
		foreach ( $sort_orders as $id => $sort_order ) {
			$topic      = $this->find( $id );
			$identifier = $this->_resolve_identifier( $id );

			$updated = $this->db->update(
				$this->table,
				array( 'sort_order' => (int) $sort_order ),
				array( $identifier['column'] => $identifier['value'] ),
			);

			if ( $updated === false ) {
				throw new \RuntimeException( "Failed to update sort order for topic {$id}: " . $this->db->last_error );
			}

			$this->_invalidate_topic_cache( $topic );
		}
	}

	/**
	 * Lock rows (Only when doing transactions)
	 *
	 * @return object[]
	 */
	public function locky_by_course( int $course_id ): array {
		return $this->db->get_results(
			$this->db->prepare(
				"SELECT * FROM {$this->table}
                WHERE id = %d
                ORDER BY sort_order ASC
                FOR UPDATE",
				$course_id
			)
		);
	}

	private function _cache_key_id( int|string $id ): string {
		return 'topic:' . $id;
	}

	private function _cache_key_course( int|string $id ): string {
		return 'course_topics:' . $id;
	}

	private function _invalidate_topic_cache( TopicRow|string|int $topic, ?int $course_id = null ): void {
		if ( $topic instanceof TopicRow ) {
			wp_cache_delete( 'topic:' . $topic->ID, self::CACHE_GROUP );
			wp_cache_delete( 'topic:' . $topic->topic_key, self::CACHE_GROUP );
			$course_id = $course_id ?? $topic->course_id;
		} else {
			wp_cache_delete( 'topic:' . $topic, self::CACHE_GROUP );
		}

		if ( $course_id ) {
			wp_cache_delete( 'course_topics:' . $course_id, self::CACHE_GROUP );
		}
	}

	/**
	 * @return array{column: string, value: int|string}
	 */
	private function _resolve_identifier( int|string $id ): array {
		if ( Randflake::validate( $id ) ) {
			return array(
				'column' => 'topic_key',
				'value'  => (string) $id,
			);
		}

		return array(
			'column' => 'ID',
			'value'  => (int) $id,
		);
	}

	/**
	 * @param string|string[] $status
	 *
	 * @return string[]
	 */
	private function _normalise_statuses( string|array $status ): array {
		if ( is_string( $status ) ) {
			$status = array( $status );
		}

		if ( in_array( 'any', $status, true ) ) {
			return self::ALLOWED_STATUSES;
		}

		return array_values(
			array_intersect( $status, self::ALLOWED_STATUSES )
		);
	}
}
