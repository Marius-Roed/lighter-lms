<?php

namespace LighterLMS\DB;

defined( 'ABSPATH' ) || exit;

use Exception;
use wpdb;

class Topic_Lessons_Repository {

	protected \wpdb $db;
	public string $table;

	public function __construct( \wpdb|null $db = null ) {
		global $wpdb;

		$this->db    = $db ?: $wpdb;
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
	* Gets all rows by a given topic id.
	*
	* @return TopicLessonRow[]
	*/
	public function find_by_topic( int $id ): ?array {
		$results = $this->db->get_results(
			$this->db->prepare(
				"SELECT * FROM {$this->table} WHERE topic_id = %d",
				$id
			)
		);

		return array_map( fn( $row ) => TopicLessonRow::from_object( $row ), $results );
	}

	/**
	* Gets all rows by a given lesson id.
	*
	* @return null|TopicLessonRow[]
	*/
	public function find_by_lesson( int $id ): ?array {
		$results = $this->db->get_results(
			$this->db->prepare(
				"SELECT * FROM {$this->table} WHERE lesson_id = %d",
				$id
			)
		);

		return array_map( fn( $row ) => TopicLessonRow::from_object( $row ), $results );
	}

	/**
	 * @param array{ topic: int, lesson: int, sort_order: ?int} $data
	 */
	public function insert( array $data ): int {
		$topic_id   = (int) ( $data['topic'] ?? $data['topic_id'] );
		$lesson_id  = (int) ( $data['lesson'] ?? $data['lesson_id'] );
		$sort_order = (int) $data['sort_order'];

		$inserted = $this->db->insert(
			$this->table,
			compact( 'topic_id', 'lesson_id', 'sort_order' ),
			array()
		);

		if ( $inserted === false ) {
			throw new Exception( 'Failed to insert topic-lesson row: ' . $this->db->last_error );
		}

		return $inserted;
	}

	public function update( int $row_id, int $sort_order ): void {
		$updated = $this->db->update(
			$this->table,
			array( 'sort_order' => $sort_order ),
			array( 'ID' => $row_id ),
			array( '%d' ),
			array( '%d' ),
		);

		if ( ! $updated ) {
			throw new Exception( 'Failed to update topic-lesson row: ' . $this->db->last_error );
		}
	}

	public function delete( int $id ): void {
		$deleted = $this->db->delete(
			$this->table,
			array( 'ID' => $id ),
			array( '%d' )
		);

		if ( $deleted === false ) {
			throw new Exception( "Failed to delete topic-lesson row $id: " . $this->db->last_error );
		}
	}

	public function delete_by_topic( int $id ): void {
		$deleted = $this->db->delete(
			$this->table,
			array( 'topic_id' => $id ),
			array( '%d' )
		);

		if ( $deleted === false ) {
			throw new Exception( "Failed to delete topic-lesson row, with topic_id $id: " . $this->db->last_error );
		}
	}

	public function bulk_update_sort_order( int $topic_id, array $ids ) {
		foreach ( $ids as $lesson_id => $sort_order ) {
			$lesson_id  = (int) $lesson_id;
			$sort_order = (int) $sort_order;

			$updated = $this->db->update(
				$this->table,
				array(
					'sort_order' => $sort_order,
				),
				array(
					'topic_id'  => $topic_id,
					'lesson_id' => $lesson_id,
				)
			);

			if ( $updated === false ) {
				throw new Exception( "Failed to delete topic-lesson row, topic_id {$topic_id}; lesson_id {$lesson_id}: " . $this->db->last_error );
			}
		}
	}
}

readonly class TopicLessonRow {
	public function __construct(
		public int $ID,
		public int $topic_id,
		public int $lesson_id,
		public int $sort_order,
	) {}

	public static function from_object( object $obj ): self {
		return new self(
			ID: $obj->ID,
			topic_id: $obj->topic_id,
			lesson_id: $obj->lesson_id,
			sort_order: $obj->sort_order,
		);
	}
}
