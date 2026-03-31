<?php

namespace LighterLMS;

use LighterLMS\DB\TopicRow;

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

	public function duplicate() {
		throw new \Exception( 'Not implemented!' );
	}

	public function move() {
		throw new \Exception( 'Not implemeneted!' );
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
			lighter()->lms->db->topic_lessons->insert( $data );
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
				lighter()->lms->db->topic_lessons->insert( $data );
			} catch ( \Throwable $e ) {
				return false;
			}
			return true;
		}

		try {
			lighter()->lms->db->topic_lessons->update( $exists->ID, $sort_order );
		} catch ( \Throwable $e ) {
			error_log( $e );
			return false;
		}

		return true;
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
