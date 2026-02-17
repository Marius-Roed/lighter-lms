<?php

namespace LighterLMS\API;

defined( 'ABSPATH' ) || exit;

use LighterLMS\Lessons;
use LighterLMS\Topics;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class Topic extends Base_Controller {
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/course/(?P<id>\d+)/topic',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_topics' ),
					'permission_callback' => array( $this, 'can_read' ),
					'args'                => $this->_get_collection_args(),
				),

				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'create_topic' ),
					'permission_callback' => array( $this, 'can_edit' ),
					'args'                => $this->_get_collection_args(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/course/(?P<id>\d+)/topic/(?P<key>[a-z0-9]+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_topic' ),
					'permission_callback' => array( $this, 'can_read' ),
					'args'                => $this->_get_single_args(),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_topic' ),
					'permission_callback' => array( $this, 'can_edit' ),
					'args'                => array(
						'id'  => array(
							'validate_callback' => fn( $v ) => is_numeric( $v ),
							'sanitize_callback' => 'absint',
						),
						'key' => array(
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);
	}

	public function can_read( WP_REST_Request $request ): bool {
		return current_user_can( 'read_post', $request->get_param( 'id' ) );
	}

	public function can_edit( WP_REST_Request $request ): bool {
		return current_user_can( 'edit_post', $request->get_param( 'id' ) );
	}

	/**
	* Get all topics based on course id.
	*
	* @param WP_REST_Request $request The current request.
	*
	* @return WP_REST_Response|WP_Error The response object. WP_Error on any error.
	*/
	public function get_topics( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$course = $this->get_post_or_error( $request->get_param( 'id' ), lighter_lms()->course_post_type );

		if ( is_wp_error( $course ) ) {
			return $course;
		}

		$topics_db = new Topics();
		$topics    = $topics_db->get_by_course( $course );

		$include = $this->_parse_include( $request );

		$topics = array_map(
			function ( $topic ) use ( $include ) {
				return $this->_prepare_topic_for_rest( $topic, $include['lessons'] ?? false );
			},
			$topics
		);

		return $this->success( $topics );
	}

	/**
	* Creates a new topic
	*
	* @param WP_REST_Request $request The current request
	*
	* @return WP_REST_Response|WP_Error The Created topic object. WP_Error on error.
	*/
	public function create_topic( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$course_id = (int) $request->get_param( 'id' );
		$course    = $this->get_post_or_error( $course_id, lighter_lms()->course_post_type );

		if ( is_wp_error( $course ) ) {
			return $course;
		}

		$args     = $request->get_json_params();
		$topic_db = new Topics();

		$new = array(
			'course_id'  => $course_id,
			'key'        => $args['key'],
			'title'      => sanitize_text_field( $args['title'] ?? '' ),
			'sort_order' => $topic_db->get_next_sort_order( $course_id ),
		);

		$topic_id = $topic_db->create( ...$new );
		if ( ! $topic_id ) {
			return $this->error( 'Could not create new course', 'course_creation_failed', 500 );
		}

		$topic = $topic_db->get( $topic_id );
		if ( ! $topic ) {
			return $this->error( 'Topic created but could not be retrieved', 'topic_retrieval_failed', 500 );
		}

		return $this->success( $topic_db::normalise_for_rest( $topic ), 201 );
	}

	/**
	* Gets a specific topic
	*
	* @param WP_REST_Request $request The current request.
	*
	* @return WP_REST_Response|WP_Error The topic object. WP_Error on failure.
	*/
	public function get_topic( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$course = $this->get_post_or_error( $request->get_param( 'id' ), lighter_lms()->course_post_type );

		if ( is_wp_error( $course ) ) {
			return $course;
		}

		$key     = $request->get_param( 'key' );
		$include = $this->_parse_include( $request );

		$topic_db = new Topics();
		$topic    = $topic_db->get( $key );

		$response = $this->_prepare_topic_for_rest( $topic, $include['lessons'] ?? false );
		return $this->success( $response );
	}

	public function delete_topic( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		throw new \Exception( 'Not implemented yet!' );
	}

	private function _prepare_topic_for_rest( mixed $topic, bool $lessons = false ): array {
		$topic = Topics::normalise_for_rest( $topic );

		if ( $lessons ) {
			$controller     = new \WP_REST_Posts_Controller( lighter_lms()->lesson_post_type );
			$post_type_slug = lighter_lms()->lesson_post_type;

			$lesson_db = new Lessons();
			$lessons   = $lesson_db->get_lessons( array( 'topic' => $topic['key'] ) );

			$topic['lessons'] = array_map(
				function ( $l ) use ( $controller, $post_type_slug ) {
					$dummy_req = new \WP_REST_Request( 'GET', "wp/v2/$post_type_slug/{$l->ID}" );
					$dummy_req->set_param( 'context', 'edit' );
					$response = $controller->prepare_item_for_response( $l, $dummy_req );
					return $controller->prepare_response_for_collection( $response );
				},
				$lessons
			);
		}

		return $topic;
	}

	private function _parse_include( WP_REST_Request $request ): array {
		$include = $request->get_param( 'include' );

		if ( is_string( $include ) ) {
			return array_fill_keys( array_map( 'trim', explode( ',', $include ) ), true );
		}

		if ( is_array( $include ) ) {
			return array_fill_keys( $include, true );
		}

		return array();
	}

	private function _get_collection_args(): array {
		return array(
			'id'      => array(
				'validate_callback' => fn( $v ) => is_numeric( $v ),
				'sanitize_callback' => 'absint',
			),
			'include' => array(
				'type'        => 'string',
				'description' => 'Comma-separated list of relations to include. Supports: lessons',
				'default'     => '',
			),
		);
	}

	private function _get_single_args(): array {
		return array(
			'id'      => array(
				'validate_callback' => fn( $v ) => is_numeric( $v ),
				'sanitize_callback' => 'absint',
			),
			'key'     => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'include' => array(
				'type'        => 'string',
				'description' => 'Comma-separated list of relations to include. Supports: lessons',
				'default'     => '',
			),
		);
	}
}
