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
	*/
	public function find_by_topic( int $id ): ?array {
		return $this->db->get_results(
			$this->db->prepare(
				"SELECT * FROM {$this->table} WHERE topic_id = %d",
				$id
			)
		);
	}

	/**
	* Gets all rows by a given lesson id.
	*/
	public function find_by_lesson( int $id ): ?array {
		return $this->db->get_results(
			$this->db->prepare(
				"SELECT * FROM {$this->table} WHERE lesson_id = %d",
				$id
			)
		);
	}

	public function insert( array $data ): int {
		$topic_id   = (int) ( $data['topic'] ?? $data['topic_id'] );
		$lesson_id  = (int) ( $data['lesson'] ?? $data['lesson_id'] );
		$sort_order = (int) $data['sort_order'];

		$inserted = $this->db->insert(
			$this->table,
			compact( $topic_id, $lesson_id, $sort_order ),
			array()
		);

		if ( $inserted === false ) {
			throw new Exception( 'Failed to insert topic-lesson row: ' . $this->db->last_error );
		}

		return $inserted;
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
