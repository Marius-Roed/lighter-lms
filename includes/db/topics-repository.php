<?php

namespace LighterLMS\DB;

defined( 'ABSPATH' ) || exit;

use wpdb;
use LighterLMS\Randflake;

class Topics_Repository {

	protected \wpdb $db;
	public string $table;

	public function __construct( \wpdb|null $db = null ) {
		global $wpdb;
		$this->db    = $db ?: $wpdb;
		$this->table = $this->db->prefix . lighter_lms()->topics_table;

		if ( ! $this->_table_exists() ) {
			_doing_it_wrong( 'Topics_Controller', 'Topics db class was instantiated with out the table present', '1.0.0' );
			return null;
		}
	}

	/**
	 * Find topic by id or key
	 *
	 * @param int|string $id ID or key of the topic.
	 * @return ?object The found topic or null.
	 */
	public function find( int|string $id ): ?object {
		$sql = "SELECT * FROM {$this->table} WHERE ";
		if ( Randflake::validate( $id ) ) {
			$sql .= 'topic_key = %s';
		} else {
			$sql .= 'ID = %d';
			$id   = (int) $id;
		}

		return $this->db->get_row(
			$this->db->prepare(
				$sql,
				$id
			)
		) ?: null;
	}

	/**
	 * Find topics by course id
	 */
	public function find_by_course( int $id ): ?array {
		return $this->db->get_results(
			$this->db->prepare(
				"SELECT * FROM {$this->table} WHERE course_id = %d ORDER BY sort_order ASC",
				$id
			)
		);
	}

	public function insert( array $data ): int {
		$topic_key  = isset( $data['topic_key'] ) && Randflake::validate( $data['topic_key'] ) ? $data['topic_key'] : Randflake::generate();
		$course_id  = (int) $data['course_id'];
		$title      = sanitize_text_field( $data['title'] );
		$sort_order = (int) $data['sort_order'];

		$inserted = $this->db->insert(
			$this->table,
			array(
				'topic_key'  => $topic_key,
				'course_id'  => $course_id,
				'title'      => $title,
				'sort_order' => $sort_order,
			),
			array()
		);

		if ( $inserted === false ) {
			throw new \Exception( 'Failed to insert topic: ' . $this->db->last_error );
		}

		return $this->db->get_var( $this->db->prepare( "SELECT ID FROM {$this->table} WHERE topic_key = %s", $topic_key ) );
	}

	public function update( int|string $id, array $data ): void {
		$field_map = array(
			'course_id'  => fn( $v ) => (int) $v,
			'title'      => fn( $v ) => sanitize_text_field( $v ),
			'sort_order' => fn( $v ) => (int) $v,
		);
		$new_data  = array_intersect_key( $data, $field_map );
		array_walk( $new_data, fn( &$v, $key ) => $v = $field_map[ $key ]( $v ) );

		if ( Randflake::validate( $id ) ) {
			if ( ! $this->find( $id ) ) {
				$this->insert( $new_data );
				return;
			}
			$new_data['topic_key'] = $id;
			$row                   = array( 'topic_key' => $id );
		} else {
			$id  = (int) $id;
			$row = array( 'ID' => $id );
		}

		$updated = $this->db->update(
			$this->table,
			$new_data,
			$row
		);

		if ( $updated === false ) {
			throw new \Exception( "Failed to update topic {$id}: " . $this->db->last_error );
		}
	}

	public function delete( int $id ): void {
		$deleted = $this->db->delete(
			$this->table,
			array( 'ID' => $id ),
			array( '%d' )
		);

		if ( $deleted === false ) {
			throw new \Exception( "Failed to delete topic {$id}: " . $this->db->last_error );
		}
	}

	public function search( string $query, string|array $status = 'publish', int $limit = 5 ) {
		if ( $status === 'any' || ( is_array( $status ) && in_array( 'any', $status ) ) ) {
			$status = array( 'publish', 'trash', 'draft', 'future', 'pending' );
		} elseif ( is_string( $status ) ) {
			$status = (array) $status;
		}

		$sql = $this->db->prepare(
			"SELECT
				t.ID,
				t.topic_key,
				t.title,
				t.sort_order,
				p.ID AS course_id,
				p.post_title AS course_title,
				CASE
					WHEN t.title LIKE `%%%s` THEN 'topic'
					WHEN p.post_title LIKE `%%%s` THEN 'course'
				END AS match_type
			FROM {$this->table} t
			JOIN {$this->db->posts} p
				ON p.ID = t.course_id
				AND p.post_type = %s
				AND p.post_status IN (%s)
			WHERE (t.title LIKE `%%%s` OR p.post_title LIKE `%%%s` )
			ORDER BY p.post_title ASC, t.sort_order ASC
			LIMIT %d",
			$query,
			$query,
			lighter_lms()->course_post_type,
			implode( ', ', $status ),
			$query,
			$query,
			$limit
		);

		return $this->db->get_results( $sql );
	}

	/**
	 * @param array<int|string, int> $sort_orders [ topic_id => sort_order, ... ]
	 */
	public function bulk_update_sort_order( array $sort_orders ): void {
		foreach ( $sort_orders as $id => $sort_order ) {
			if ( is_numeric( $id ) ) {
				$where = 'ID';
			} else {
				$where = 'topic_key';
			}

			$updated = $this->db->update(
				$this->table,
				array( 'sort_order' => $sort_order ),
				array( $where => $id ),
			);

			if ( $updated === false ) {
				throw new \Exception( "Failed to update sort order for topic {$id}: " . $this->db->last_error );
			}
		}
	}

	/**
	 * Lock rows (Only when doing transactions)
	 *
	 * @param int $id The topic id
	 *
	 * @return object[]
	 */
	public function locky_by_course( int $id ): array {
		return $this->db->get_results(
			$this->db->prepare(
				"SELECT * FROM {$this->table} WHERE id = %d ORDER BY sort_order ASC FOR UPDATE",
				$id
			)
		);
	}

	public static function normalise_for_rest( object $topic, bool $withLesson = false ): array {
		$lessons   = array();
		$lesson_db = new Topic_Lessons_Controller();

		if ( $withLesson ) {
			$lessons = $lesson_db->get_lessons( array( 'topic' => $topic->topic_key ) );
			if ( ! empty( $lessons ) ) {
				$lessons = $lesson_db->normalise_for_rest( $lessons );
			}
		}

		return array(
			'id'        => (int) $topic->ID,
			'key'       => $topic->topic_key,
			'course'    => (int) $topic->course_id,
			'title'     => $topic->title,
			'sortOrder' => (int) $topic->sort_order,
			'updated'   => $topic->updated_at,
			'created'   => $topic->created_at,
			'lessons'   => $lessons,
		);
	}

	private function _table_exists(): bool {
		$found = $this->db->get_var(
			$this->db->prepare( 'SHOW TABLES LIKE %s', $this->table )
		);

		return $found === $this->table;
	}
}
