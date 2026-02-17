<?php

namespace LighterLMS\API;

defined( 'ABSPATH' ) || exit;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class Course extends Base_Controller {

	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/courses/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_course' ),
					'permission_callback' => array( $this, 'can_read' ),
					'args'                => array(
						'id'     => array(
							'validate_callback' => fn( $v ) => is_numeric( $v ),
							'sanitize_callback' => 'absint',
						),
						'topics' => array(
							'required'          => false,
							'type'              => 'boolean',
							'description'       => 'Appends an array of all topics and their lessons.',
							'validate_callback' => fn( $v ) => wp_validate_boolean( $v ),
							'sanitze_callback'  => 'wp_validate_boolean',
						),
					),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_course' ),
					'permission_callback' => array( $this, 'can_delete' ),
				),
			)
		);
	}

	public function can_read( WP_REST_Request $request ): bool {
		return current_user_can( 'read_post', $request->get_param( 'id' ) );
	}

	public function can_delete( WP_REST_Request $request ): bool {
		return current_user_can( 'delete_post', $request->get_param( 'id' ) );
	}

	public function get_course( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$post = $this->get_post_or_error( $request->get_param( 'id' ), lighter_lms()->course_post_type );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$data = $this->format_post( $post );
		if ( $request->get_param( 'topics' ) ) {
			// TODO: Get topics $data['topics'] = $this->get_topics( $post->ID );
		}

		return $this->success( $data );
	}

	public function delete_course( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$post = $this->get_post_or_error( $request->get_param( 'id' ), lighter_lms()->course_post_type );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		// TODO: DELETE Topics and maybe lessons.

		return $this->success(
			array(
				'delted' => true,
				'id'     => $post->ID,
			)
		);
	}
}
