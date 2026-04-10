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

	protected \wpdb $db;
	public string $table;

	public function __construct( \wpdb|null $db = null ) {
		global $wpdb;
		$this->db    = $db ?? $wpdb;
		$this->table = $this->db->prefix . lighter_lms()->topics_table;
	}

	public function find( int|string $id ): ?TopicRow {
		$identifier = $this->_resolve_identifier( $id );
		$format     = $identifier['column'] === 'ID' ? '%d' : '%s';

		$row = $this->db->get_row(
			$this->db->prepare(
				"SELECT * FROM {$this->table}
                WHERE {$identifier['column']} = {$format}",
				$identifier['value']
			)
		);

		if ( ! $row ) {
			return null;
		}

		return new TopicRow( ...(array) $row );
	}

	/**
	 * @return TopicRow[]
	 */
	public function find_by_course( int $course_id ): array {
		$results = $this->db->get_results(
			$this->db->prepare(
				"SELECT * FROM {$this->table}
                WHERE course_id = %d
                ORDER BY sort_order ASC",
				$course_id
			)
		);

		return array_map( fn( $row ) => new TopicRow( ...(array) $row ), $results );
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

		$identifier = $this->_resolve_identifier( $id );

		$updated = $this->db->update(
			$this->table,
			$cleaned,
			array( $identifier['column'] => $identifier['value'] )
		);

		if ( $updated === false ) {
			throw new \RuntimeException( "Failed to update topic {$id}: " . $this->db->last_error );
		}
	}

	/**
	 * @throws \RuntimeException
	 */
	public function delete( int $id ): void {
		$deleted = $this->db->delete(
			$this->table,
			array( 'ID' => $id ),
			array( '%d' )
		);

		if ( $deleted === false ) {
			throw new \RuntimeException( "Failed to delete topic {$id}: " . $this->db->last_error );
		}
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
				p.post_title AS course_title,
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
			$identifier = $this->_resolve_identifier( $id );

			$updated = $this->db->update(
				$this->table,
				array( 'sort_order' => (int) $sort_order ),
				array( $identifier['column'] => $identifier['value'] ),
			);

			if ( $updated === false ) {
				throw new \RuntimeException( "Failed to update sort order for topic {$id}: " . $this->db->last_error );
			}
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

	/**
	 * @return array{column: string, value: int|string}
	 */
	private function _resolve_identifier( int|string $id ) {
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
