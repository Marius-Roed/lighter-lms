<?php

namespace LighterLMS\DB;

defined( 'ABSPATH' ) || exit;

/**
 * @phpstan-type TopicLessonRow object{ID: int, topic_id: int, lesson_id: int, sort_order: int}
 */
class Topic_Lessons_Repository {

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
		return $this->db->get_row(
			$this->db->prepare(
				"SELECT * FROM {$this->table} WHERE topic_id = %d AND lesson_id = %d",
				$topic_id,
				$lesson_id
			)
		) ?: null;
	}

	/**
	 * @return TopicLessonRow[]|null
	 */
	public function find_by_topic( int $id, bool $include_trashed = false ): ?array {
		if ( $include_trashed ) {
			return $this->db->get_results(
				$this->db->prepare(
					"SELECT * FROM {$this->table} WHERE topic_id = %d",
					$id
				)
			);
		} else {
		}

		return $this->db->get_results(
			$this->db->prepare(
				"SELECT * FROM {$this->table} tl
                INNER JOIN {$this->db->posts} p ON p.ID = tl.lesson_id
                WHERE p.post_status != 'trash' AND tl.topic_id = %d",
				$id
			)
		);
	}

	/**
	 * @return TopicLessonRow[]|null
	 */
	public function find_by_lesson( int $id ): ?array {
		return $this->db->get_results(
			$this->db->prepare(
				"SELECT * FROM {$this->table} WHERE lesson_id = %d",
				$id
			)
		);
	}

	/**
	 * @throws \RuntimeException
	 */
	public function insert( int $topic_id, int $lesson_id, int $sort_order ): int {
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
	 * @param array{ topic_id: int, lesson_id: int, sort_order: int } $data The new data to update the row
	 *
	 * @throws \RuntimeException
	 */
	public function update( int $row_id, array $data ): void {
		$allowed  = array( 'topic_id', 'lesson_id', 'sort_order' );
		$new_data = array_map( 'intval', array_intersect_key( $data, array_flip( $allowed ) ) );

		if ( empty( $new_data ) ) {
			return;
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
		}
	}

	/**
	 * @param array<string, int> $where
	 *
	 * @throws \RuntimeException
	 */
	private function _delete_where( array $where ) {
		$deleted = $this->db->delete(
			$this->table,
			$where,
			array( '%d' )
		);

		if ( $deleted === false ) {
			$label = array_values( $where )[0];
			$id    = array_keys( $where )[0];
			throw new \RuntimeException(
				"Failed to delete topic-lesson row with $label ($id): "
				. $this->db->last_error
			);
		}
	}
}
