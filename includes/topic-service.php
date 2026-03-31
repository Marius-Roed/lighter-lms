<?php

namespace LighterLMS;

defined( 'ABSPATH' ) || exit;

class Topic_Service {
	public function create( int $course_id, string $title ): object {
		$siblings   = lighter()->lms->db->topics->find_by_course( $course_id );
		$sort_order = count( $siblings ) * 10;

		try {
			$id = lighter()->lms->db->topics->insert(
				compact(
					'course_id',
					'title',
					'sort_order',
				)
			);
		} catch ( \Throwable $e ) {
			return new \WP_Error( 'failed_topic_creation', 'LighterLMS: ' . $e );
		}

		return lighter()->lms->db->topics->find( $id );
	}

	public function get( int|string $id ): ?Topic {
		$topic = lighter()->lms->db->topics->find( $id );

		if ( ! $topic ) {
			// NOTE: Maybe log or notify not found.
			return null;
		}

		return new Topic(
			ID: $topic->ID,
			title: $topic->title,
			topic_key: $topic->topic_key,
			course_id: $topic->course_id,
			sort_order: $topic->sort_order,
			created_at: $topic->created_at,
			updated_at: $topic->updated_at,
		);
	}

	public function get_by_course( int $id ): array {
		return lighter()->lms->db->topics->find_by_course( $id );
	}

	/**
	 * @return \WP_Post[] The found lessons as post objects.
	 */
	public function get_lessons( int|string $id ): array {
		$topic = $this->get( $id );

		if ( ! $topic ) {
			return array();
		}

		$lessons_raw = lighter()->lms->db->topic_lessons->find_by_topic( $topic->ID );

		$lessons = array_map( fn( $l ) => get_post( $l->lesson_id ), $lessons_raw );

		return $lessons;
	}

	public function get_lesson_count( int|string $id ): int {
		$topic = $this->get( $id );
		if ( ! $topic ) {
			return 0;
		}

		$lessons = lighter()->lms->db->topic_lessons->find_by_topic( $topic->ID );

		return count( $lessons );
	}

	public function rename( int|string $id, string $title ): void {
		$topic = $this->get( $id );

		if ( ! $topic ) {
			return;
		}

		lighter()->lms->db->topics->update( $id, compact( 'title' ) );
	}

	/**
	* Move a topic to a new position. Reorders all sibling topics.
	*
	* @param string[]|int[] $ordered_topic_ids All topic ids/keys in the desired new order, including the $id.
	*/
	public function move( int|string $id, int $course_id, array $ordered_topic_ids ): void {
		lighter()->lms->db->start_transaction();

		try {
			lighter()->lms->db->topics->locky_by_course( $course_id );

			$topic = $this->get( $id );

			if ( ! $topic ) {
				throw new \Error( "Topic of identifier $id not found" );
			}

			lighter()->lms->db->topics->update( $id, compact( 'course_id' ) );

			$sort_orders = array();
			foreach ( array_values( $ordered_topic_ids ) as $index => $topic_id ) {
				$sort_orders[ $topic_id ] = ( $index + 1 ) * 10;
			}

			lighter()->lms->db->topics->bulk_update_sort_order( $sort_orders );

			lighter()->lms->db->commit();
		} catch ( \Throwable $e ) {
			lighter()->lms->db->rollback();
		}
	}

	public function duplicate( int|string $id, int $course_id ): ?object {
		$topic = $this->get( $id );
		if ( ! $topic ) {
			return null;
		}

		$siblings = lighter()->lms->db->topics->find_by_course( $course_id );

		lighter()->lms->db->start_transaction();

		try {
			$new_id = lighter()->lms->db->topics->insert(
				array(
					'course_id'  => $course_id,
					'title'      => $topic->title + ' (copy)',
					'sort_order' => ( count( $siblings ) + 1 ) * 10,
				)
			);

			$lessons = lighter()->lms->db->topic_lessons->find_by_topic( $topic->ID );

			foreach ( $lessons as $link ) {
				lighter()->lms->db->topic_lessons->insert(
					array(
						'topic_id'   => $new_id,
						'lesson_id'  => (int) $link->lesson_id,
						'sort_order' => (int) $link->sort_order,
					)
				);
			}

			lighter()->lms->db->commit();
		} catch ( \Throwable $e ) {
			lighter()->lms->db->rollback();
		}

		return lighter()->lms->db->topics->find( $new_id );
	}

	public function search( string $query ): array {
		if ( mb_strlen( trim( $query ) ) < 2 ) {
			_doing_it_wrong( __FUNCTION__, 'Cannot call search on a string shorter than 2 characters', '1.0.0' );
			return array();
		}

		$rows   = lighter()->lms->db->topics->search( $query );
		$groups = array();

		foreach ( $rows as $row ) {
			$course_id = (int) $row->course_id;

			if ( ! isset( $groups[ $course_id ] ) ) {
				$groups[ $course_id ] = array(
					'match_type'   => $row->match_type,
					'course_id'    => $course_id,
					'course_title' => $row->course_title,
					'topics'       => array(),
				);
			}

			$groups[ $course_id ]['topics'][] = array(
				'ID'         => (int) $row->topic_id,
				'key'        => $row->topic_key,
				'title'      => $row->topic_title,
				'sort_order' => (int) $row->sort_order,
			);
		}

		return array_values( $groups );
	}

	public function delete( int|string $id ): void {
		$topic = $this->get( $id );
		if ( ! $topic ) {
			return;
		}

		lighter()->lms->db->start_transaction();

		try {
			lighter()->lms->db->topic_lessons->delete_by_topic( $topic->ID );
			lighter()->lms->db->topics->delete( $topic->ID );
			lighter()->lms->db->commit();
		} catch ( \Throwable $e ) {
			lighter()->lms->db->rollback();
		}
	}

	public static function normalise_for_rest( object $topic, bool $with_lessons = false ): object {
		$rest_item = (object) array(
			'key'       => $topic->topic_key,
			'title'     => $topic->title,
			'courseId'  => (int) $topic->course_id,
			'sortOrder' => (int) $topic->sort_order,
			'updatedAt' => $topic->updated_at,
		);

		if ( $with_lessons ) {
			$lessons            = lighter()->lms->topic->get_lessons( $topic->ID );
			$rest_item->lessons = array_map(
				array( lighter()->lms->lesson::class, 'normalise_for_rest' ),
				$lessons
			);
		}

		return $rest_item;
	}
}

readonly class Topic {
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
