<?php

namespace LighterLMS\API;

defined( 'ABSPATH' ) || exit;

use LighterLMS\Randflake;
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
					'methods'             => 'PATCH',
					'callback'            => array( $this, 'update_topic' ),
					'permission_callback' => array( $this, 'can_edit' ),
					'args'                => $this->_get_update_args(),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_topic' ),
					'permission_callback' => array( $this, 'can_edit' ),
					'args'                => array(
						'id'  => array(
							'validate_callback' => fn( $v ) => is_numeric( $v ),
							'sanitize_callback' => 'absint',
							'required'          => true,
						),
						'key' => array(
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'required'          => true,
							'type'              => 'string',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/course/(?P<id>\d+)/topic-move',
			array(
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'move_topic' ),
					'permission_callback' => array( $this, 'can_edit' ),
					'args'                => array(
						'id'        => array(
							'validate_callback' => fn( $v ) => is_numeric( $v ),
							'sanitize_callback' => 'absint',
							'required'          => true,
							'description'       => 'The ID of the course post',
							'type'              => 'integer',
						),
						'topic_key' => array(
							'validate_callback' => fn( $v ) => Randflake::validate( $v ),
							'sanitize_callback' => array( Randflake::class, 'sanitize' ),
							'required'          => true,
							'description'       => 'The key of the topic to move',
							'type'              => 'string',
						),
						'reordered' => array(
							'required'    => true,
							'description' => 'An array of the reordered topics. In form of { `index`: `topic_key` }',
							'type'        => 'array',
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

		$topics = lighter()->lms->course->get_topics( $course->ID );

		$include = $this->_parse_include( $request );

		$topics = array_map(
			function ( $topic ) use ( $include ) {
				return $this->_prepare_topic_for_rest( $topic, $include['lessons'] ?? false );
			},
			$topics
		);

		return $this->success( $topics );
	}

	public function create_topic( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$course_id = (int) $request->get_param( 'id' );
		$course    = $this->get_post_or_error( $course_id, lighter_lms()->course_post_type );

		if ( is_wp_error( $course ) ) {
			return $course;
		}

		$args  = $request->get_json_params();
		$title = sanitize_text_field( $args['title'] ?? '' );

		$topic = lighter()->lms->topic->create( $course_id, $title );

		if ( is_wp_error( $topic ) ) {
			return $topic;
		}

		return $this->success( lighter()->lms->topic::normalise_for_rest( $topic ), 201 );
	}

	public function get_topic( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$course = $this->get_post_or_error( $request->get_param( 'id' ), lighter_lms()->course_post_type );

		if ( is_wp_error( $course ) ) {
			return $course;
		}

		$key     = $request->get_param( 'key' );
		$include = $this->_parse_include( $request );

		$topic = lighter()->lms->topic->get( $key );

		$response = $this->_prepare_topic_for_rest( $topic, $include['lessons'] ?? false );
		return $this->success( $response );
	}

	public function update_topic( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$course = $this->get_post_or_error( $request->get_param( 'id' ), lighter_lms()->course_post_type );

		if ( is_wp_error( $course ) ) {
			return $course;
		}

		$key   = $request->get_param( 'key' );
		$topic = lighter()->lms->topic->get( $key );
		$title = $request->get_param( 'title' );

		if ( empty( $topic ) ) {
			return $this->error( "Failed to find topic of key $key", 'could_not_find_topic' );
		}

		lighter()->lms->topic->rename( $topic->ID, $title );

		$topic = lighter()->lms->topic->get( $key );

		$response = $this->_prepare_topic_for_rest( $topic );
		return $this->success( $response );
	}

	public function move_topic( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$course = $this->get_post_or_error( $request->get_param( 'id' ), lighter_lms()->course_post_type );

		if ( is_wp_error( $course ) ) {
			return $course;
		}

		$topic_key = $request->get_param( 'topic_key' );
		$reordered = $request->get_param( 'reordered' );

		lighter()->lms->topic->move( $topic_key, $course->ID, $reordered );

		$new_order = lighter()->lms->course->get_topics( $course->ID );
		$new_order = array_map( array( $this, '_prepare_topic_for_rest' ), $new_order );

		return $this->success( $new_order );
	}

	public function delete_topic( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$course = $this->get_post_or_error( $request->get_param( 'id' ), lighter_lms()->course_post_type );

		if ( is_wp_error( $course ) ) {
			return $course;
		}

		$key = $request->get_param( 'key' );

		$topic = lighter()->lms->topic->get( $key );

		if ( empty( $topic ) ) {
			return $this->error( "Failed to find topic of key \"$key\"", 'failed_topic_deletion', 500 );
		}

		lighter()->lms->topic->delete( $topic->ID );

		$topics = lighter()->lms->course->get_topics( $course->ID );

		return $this->success( $topics );
	}

	private function _prepare_topic_for_rest( mixed $topic, bool $lessons = false ): object {
		return lighter()->lms->topic::normalise_for_rest( (object) $topic, $lessons );
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

	private function _get_update_args(): array {
		return array();
	}
}
