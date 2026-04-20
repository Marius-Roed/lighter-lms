<?php

namespace LighterLMS\DB;

defined( 'ABSPATH' ) || exit;

/**
 * @phpstan-type TopicLessonRow object{ID: int, topic_id: int, lesson_id: int, sort_order: int}
 */
class Topic_Lessons_Repository {

	const CACHE_GROUP = 'lighter_lms_topic_lessons';

	protected \wpdb $db;
	public string $table;

	public function __construct( ?\wpdb $db = null ) {
		global $wpdb;

		$this->db    = $db ?? $wpdb;
		$this->table = $this->db->prefix . lighter_lms()->topic_lessons_table;
	}

	/**
	 * Gets the row with given topic and lesson id
	 */
	public function find( int $topic_id, int $lesson_id ): ?object {
		$c_key  = "topic_lesson:$topic_id:$lesson_id";
		$cached = wp_cache_get( $c_key, self::CACHE_GROUP );

		if ( $cached !== false ) {
			return $cached ?: null;
		}

		$topic = $this->db->get_row(
			$this->db->prepare(
				"SELECT * FROM {$this->table} WHERE topic_id = %d AND lesson_id = %d",
				$topic_id,
				$lesson_id
			)
		) ?: null;

		wp_cache_set( $c_key, $topic ?? 0, self::CACHE_GROUP, HOUR_IN_SECONDS );

		return $topic;
	}

	/**
	 * @return TopicLessonRow[]|null
	 */
	public function find_by_topic( int $id, bool $include_trashed = false ): ?array {
		$c_key  = "topic:$id:" . (int) $include_trashed;
		$cached = wp_cache_get( $c_key, self::CACHE_GROUP );

		if ( $cached !== false ) {
			return $cached ?: null;
		}

		$sql = "SELECT * FROM {$this->table} WHERE topic_id = %d";
		if ( ! $include_trashed ) {
			$sql = "SELECT * FROM {$this->table} tl
                INNER JOIN {$this->db->posts} p ON p.ID = tl.lesson_id
                WHERE p.post_status != 'trash' AND tl.topic_id = %d";
		}

		$rows = $this->db->get_results(
			$this->db->prepare(
				$sql,
				$id
			)
		);

		wp_cache_set( $c_key, $rows ?? 0, self::CACHE_GROUP, HOUR_IN_SECONDS );

		return $rows;
	}

	/**
	 * @return TopicLessonRow[]|null
	 */
	public function find_by_lesson( int $id ): ?array {
		$c_key  = 'lesson:' . $id;
		$cached = wp_cache_get( $c_key, self::CACHE_GROUP );

		if ( $cached !== false ) {
			return $cached ?: null;
		}

		$rows = $this->db->get_results(
			$this->db->prepare(
				"SELECT * FROM {$this->table} WHERE lesson_id = %d",
				$id
			)
		);

		wp_cache_set( $c_key, $rows ?? 0, self::CACHE_GROUP, HOUR_IN_SECONDS );

		return $rows;
	}

	/**
	 * @throws \RuntimeException
	 */
	public function insert( int $topic_id, int $lesson_id, int $sort_order = 10 ): int {
		$inserted = $this->db->insert(
			$this->table,
			compact( 'topic_id', 'lesson_id', 'sort_order' ),
			array( '%d', '%d', '%d' )
		);

		if ( $inserted === false ) {
			throw new \RuntimeException(
				'Failed to insert topic-lesson row: ' . $this->db->last_error
			);
		}

		return $inserted;
	}

	/**
	 * @param array{ topic_id: ?int, lesson_id: ?int, sort_order: ?int } $data The new data to update the row
	 *
	 * @throws \RuntimeException
	 */
	public function update( int $row_id, array $data ): void {
		$allowed  = array( 'topic_id', 'lesson_id', 'sort_order' );
		$new_data = array_map( 'intval', array_intersect_key( $data, array_flip( $allowed ) ) );

		if ( empty( $new_data ) ) {
			return;
		}

		$existing = $this->db->get_row( $this->db->prepare( "SELECT * FROM {$this->table} WHERE ID = %d", $row_id ) );
		if ( ! $existing ) {
			throw new \RuntimeException( "Cannot update topic_lesson with unknown ID \"$row_id\"." );
		}

		$updated = $this->db->update(
			$this->table,
			$new_data,
			array( 'ID' => $row_id ),
			array_fill( 0, count( $new_data ), '%d' ),
			array( '%d' ),
		);

		if ( $updated === false ) {
			throw new \RuntimeException( "Failed to update topic-lesson row ($row_id): " . $this->db->last_error );
		}

		$this->_invalidate_pair( (int) $existing->topic_id, (int) $existing->lesson_id );
	}

	public function delete( int $id ): void {
		$this->_delete_where( array( 'ID' => $id ) );
	}

	public function delete_by_topic( int $id ): void {
		$this->_delete_where( array( 'topic_id' => $id ) );
	}

	/**
	 * @param array<string, int> $ids Map of lesson_id => sort_order
	 */
	public function bulk_update_sort_order( int $topic_id, array $ids ) {
		foreach ( $ids as $lesson_id => $sort_order ) {
			$updated = $this->db->update(
				$this->table,
				array(
					'sort_order' => (int) $sort_order,
				),
				array(
					'topic_id'  => $topic_id,
					'lesson_id' => (int) $lesson_id,
				)
			);

			if ( $updated === false ) {
				throw new \RuntimeException(
					"Failed to delete topic-lesson row, topic_id {$topic_id}; lesson_id {$lesson_id}: "
					. $this->db->last_error
				);
			}

			$this->_invalidate_pair( $topic_id, $lesson_id );
		}
	}

	/**
	 * @param array<string, int> $where
	 *
	 * @throws \RuntimeException
	 */
	private function _delete_where( array $where ): void {
		$column = array_key_first( $where );
		$value  = $where[ $column ];

		$rows = $this->db->get_results(
			$this->db->prepare(
				"SELECT topic_id, lesson_id FROM {$this->table} WHERE {$column} = %d",
				$value
			)
		);

		if ( empty( $rows ) ) {
			return;
		}

		$deleted = $this->db->delete( $this->table, $where, array( '%d' ) );

		if ( $deleted === false ) {
			throw new \RuntimeException(
				"Failed to delete topic-lesson row with $column ($value): "
				. $this->db->last_error
			);
		}

		foreach ( $rows as $row ) {
			$this->_invalidate_pair( (int) $row->topic_id, (int) $row->lesson_id );
		}
	}

	private function _cache_key_pair( int $topic_id, int $lesson_id ): string {
		return "topic_lesson:{$topic_id}:{$lesson_id}";
	}

	private function _invalidate_pair( int $topic_id, int $lesson_id ): void {
		wp_cache_delete( $this->_cache_key_pair( $topic_id, $lesson_id ), self::CACHE_GROUP );
		wp_cache_delete( "topic:{$topic_id}:0", self::CACHE_GROUP );
		wp_cache_delete( "topic:{$topic_id}:1", self::CACHE_GROUP );
		wp_cache_delete( "lesson:{$lesson_id}", self::CACHE_GROUP );
	}
}
