<?php

namespace LighterLMS;

defined( 'ABSPATH' ) || exit;

/**
 * @phpstan-import-type ReorderData from Types
 * @phpstan-import-type LessonReorder from Types
 */
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

	public function get( int|string|Topic $id ): ?Topic {
		if ( $id instanceof Topic ) {
			return $id;
		}
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

	/**
	 * @param ReorderData  $to_data The new lesson data of the topic being moved to.
	 * @param ?ReorderData $from_data The new lesson data of the topic being moved from. Can be null if the lesson is being moved within the same topic.
	 */
	public function reorder_lessons( array $to_data, ?array $from_data = null ): \WP_Error|Topic {
		$to_topic   = lighter()->lms->topic->get( $to_data['topic_key'] );
		$from_topic = $from_data ? lighter()->lms->topic->get( $from_data['topic_key'] ) : $to_topic;

		if ( ! $to_topic || ! $from_topic ) {
			return new \WP_Error( 'topic_not_found', 'Could not find topic to reorder', compact( 'to_topic', 'from_topic', 'to_data', 'from_data' ) );
		}

		if ( $to_topic->topic_key === $from_topic->topic_key ) {
			$reordered = $this->_reorder_lessons( $to_data['reorder'], $to_topic );
		} else {
			$reordered = $this->_reorder_lessons( $to_data['reorder'], $to_topic, $from_data['reorder'], $from_topic );
		}

		if ( is_wp_error( $reordered ) ) {
			return $reordered;
		}

		return lighter()->lms->topic->get( $to_data['topic_key'] );
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

	/**
	 * @param LessonReorder[]  $new_order
	 * @param ?LessonReorder[] $old_order
	 */
	private function _reorder_lessons(
		array $new_order,
		Topic|string|int $topic,
		?array $old_order = null,
		Topic|string|int|null $from_topic = null
	) {
		if ( ! empty( $old_order ) && $from_topic === null ) {
			// NOTE: $old_order cannot have something and $from_topic be null.
			// This is why we simply return. Maybe a there is a better way to
			// handle this.
			return;
		}

		$topic      = lighter()->lms->topic->get( $topic );
		$from_topic = ! empty( $old_order ) ? lighter()->lms->topic->get( $from_topic ) : $topic;

		if ( ! $topic ) {
			return;
		}

		lighter()->lms->db->start_transaction();

		try {
			foreach ( $new_order as $lesson ) {
				$topic_lesson = lighter()->lms->db->topic_lessons->find( $from_topic->ID, $lesson['id'] );
				if ( ! $topic_lesson ) {
					continue;
				}

				lighter()->lms->db->topic_lessons->update(
					$topic_lesson->ID,
					array(
						'topic_id'   => $topic->ID,
						'sort_order' => $lesson['sort_order'],
					)
				);
			}

			if ( ! empty( $old_order ) ) {
				foreach ( $old_order as $lesson ) {
					$topic_lesson = lighter()->lms->db->topic_lessons->find( $from_topic->ID, $lesson['id'] );
					if ( ! $topic_lesson ) {
						continue;
					}

					lighter()->lms->db->topic_lessons->update(
						$topic_lesson->ID,
						array(
							'topic_id'   => $from_topic->ID,
							'sort_order' => $lesson['sort_order'],
						)
					);
				}
			}

			lighter()->lms->db->commit();
		} catch ( \Throwable $e ) {
			lighter()->lms->db->rollback();
			return new \WP_Error(
				'failed_topic_reorder',
				"Could not reorder topic: {$e->getMessage()}",
				array(
					'from_topic' => $from_topic,
					'from_data'  => $old_order,
					'to_topic'   => $topic,
					'to_order'   => $new_order,
				)
			);
		}
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
