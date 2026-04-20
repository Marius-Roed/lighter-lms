<?php

namespace LighterLMS;

use LighterLMS\DB\TopicRow;
use WP_Error;

defined( 'ABSPATH' ) || exit;

class Lesson_Service {
	public readonly Lesson_Post $post;

	public function __construct() {
		$this->post = new Lesson_Post();
	}

	public function get_course( int|\WP_Post $lesson ): array {
		$lesson     = get_post( $lesson );
		$topics_raw = lighter()->lms->db->topic_lessons->find_by_lesson( $lesson->ID );

		$courses = array_map(
			fn( $tl ) => get_post( lighter()->lms->db->topics->find( $tl->topic_id )->course_id ),
			$topics_raw
		);

		return $courses;
	}

	/**
	 * @return TopicRow[]
	 */
	public function get_parent_topics( int|\WP_Post $lesson ): array {
		$lesson = get_post( $lesson );

		$topics_raw = lighter()->lms->db->topic_lessons->find_by_lesson( $lesson->ID );

		$topics = array_map(
			fn( $tl ) => lighter()->lms->db->topics->find( $tl->topic_id ),
			$topics_raw
		);

		return array_filter( $topics );
	}

	public function get_topic_data( \WP_Post $lesson, TopicRow $topic ): array {
		$topic_lesson = lighter()->lms->db->topic_lessons->find( $topic->ID, $lesson->ID );
		return array(
			'key'        => $topic->topic_key,
			'title'      => $topic->title,
			'sort_order' => (int) $topic_lesson->sort_order,
		);
	}

	public function duplicate() {
		throw new \Exception( 'Not implemented!' );
	}

	public function move( \WP_Post $lesson, int $sort_order, Topic $topic ) {
		$topic_id  = $topic->ID;
		$lesson_id = $lesson->ID;

		$topic_lesson = lighter()->lms->db->topic_lessons->find( $topic_id, $lesson_id );

		if ( empty( $topic_lesson ) ) {
			return;
		}

		lighter()->lms->db->topic_lessons->update(
			$topic_lesson->ID,
			compact( 'topic_id', 'lesson_id', 'sort_order' )
		);
	}

	/**
	 * @param array{ id: int, key: string, sort_order: int }[] $lesson_data
	 */
	public function reoder_topic( array $lessons_data, Topic $topic ): \WP_Error|Topic {
		lighter()->lms->db->start_transaction();

		try {
			foreach ( $lessons_data as $lesson ) {
				list( 'key' => $lesson_key, 'sort_order' => $sort_order ) = $lesson;
				$lesson = get_post( $lesson['id'] );

				if ( $lesson->post_type !== lighter_lms()->lesson_post_type
				|| get_post_meta( $lesson->ID, '_lighter_lesson_key', true ) !== $lesson_key ) {
					continue;
				}

				$topic_lesson = lighter()->lms->db->topic_lessons->find( $topic->ID, $lesson->ID );
				if ( empty( $topic_lesson ) ) {
					throw new \Exception( "Topic_lesson of topic_id {$topic->ID}, lesson_id {$lesson->ID}  not found" );
				}

				lighter()->lms->db->topic_lessons->update(
					$topic_lesson->ID,
					array(
						'lesson_id'  => $lesson->ID,
						'sort_order' => $sort_order,
						'topic_id'   => $topic->ID,
					)
				);
			}
			lighter()->lms->db->commit();
		} catch ( \Throwable $e ) {
			return new \WP_Error( 'failed_topic_reorder', "Could not update the order for topic \"{$topic->title}\": {$e->getMessage()}" );
			lighter()->lms->db->rollback();
		}

		return $topic;
	}

	/**
	 * Creates a relationship between a lesson and a topic
	 *
	 * @param array{ topic_id: int, lesson_id: int, sort_order: int } $data
	 */
	public function create_topic_relationship( array $data ): void {
		$topics = lighter()->lms->db->topic_lessons->find_by_lesson( $data['lesson_id'] );
		if ( ! $topics ) {
			return;
		}

		foreach ( $topics as $topic ) {
			if ( $topic->ID == $data['topic_id'] ) {
				return;
			}
		}

		try {
			lighter()->lms->db->topic_lessons->insert( ...$data );
		} catch ( \Throwable $e ) {
		}
	}

	/**
	 * Updates the relation between a lesson and topic.
	 * Creates one if no already existant
	 *
	 * @param array{ topic_id: int, lesson_id: int, sort_order: int } $data
	 */
	public function update_topic_relationship( array $data ): bool {
		[
			'topic_id' => $topic_id,
			'lesson_id' => $lesson_id,
			'sort_order' => $sort_order
		] = $data;

		$exists = lighter()->lms->db->topic_lessons->find( $topic_id, $lesson_id );
		if ( ! $exists ) {

			try {
				lighter()->lms->db->topic_lessons->insert( ...$data );
			} catch ( \Throwable $e ) {
				return false;
			}
			return true;
		}

		try {
			lighter()->lms->db->topic_lessons->update( $exists->ID, compact( 'sort_order' ) );
		} catch ( \Throwable $e ) {
			error_log( $e );
			return false;
		}

		return true;
	}

	public function delete_topic_relationship( int|\WP_Post $lesson ): void {
		$post = get_post( $lesson );

		if ( $post->post_type !== lighter_lms()->lesson_post_type ) {
			_doing_it_wrong( __FUNCTION__, "Cannot call topic-lesson delete on a post of type \"$post->post_type\"", '1.0.0' );
			return;
		}

		$exists = lighter()->lms->db->topic_lessons->find_by_lesson( $post->ID );
		if ( ! $exists ) {
			return;
		}

		foreach ( $exists as $lesson_row ) {
			try {
				lighter()->lms->db->topic_lessons->delete( $lesson_row->ID );
			} catch ( \Throwable $e ) {
				error_log( $e );
			}
		}
	}

	public static function normalise_for_rest( int|\WP_Post $lesson, $context = 'edit' ) {
		global $post;
		$original_post = $post; // WARN: as `prepare_item_for_reponse` changes the global $post variable
								// we have to save it and rewrite to it.

		$post = get_post( $lesson );

		$controller = new \WP_REST_Posts_Controller( lighter_lms()->lesson_post_type );
		$request    = new \WP_REST_Request( 'GET', "/wp/v2/lighter_lessons/$post->ID" );
		$request->set_param( 'context', $context );

		$data = $controller->prepare_item_for_response( $post, $request )->get_data();

		$post = $original_post;
		wp_reset_postdata();

		return $data;
	}
}
