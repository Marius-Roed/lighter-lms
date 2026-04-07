<?php

namespace LighterLMS\API;

defined( 'ABSPATH' ) || exit;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class Lesson extends Base_Controller {

	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/lesson/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_lesson' ),
					'permission_callback' => array( $this, 'can_read' ),
					'args'                => array(
						'id' => array(
							'validate_callback' => fn( $v ) => is_numeric( $v ),
							'sanitize_callback' => 'absint',
						),
					),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_lesson' ),
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

	public function get_lesson( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$post = $this->get_post_or_error( $request->get_param( 'id' ), lighter_lms()->lesson_post_type );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$data = $this->format_post( $post );
		if ( $request->get_param( 'topics' ) ) {
			$data['topics'] = lighter()->lms->course->get_topics( $post->ID );
		}

		return $this->success( $data );
	}

	public function delete_lesson( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$post = $this->get_post_or_error( $request->get_param( 'id' ), lighter_lms()->lesson_post_type );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		// TODO: DELETE Topics and maybe lessons.

		return $this->success(
			array(
				'deleted' => true,
				'id'      => $post->ID,
			)
		);
	}

	public function pre_save_lesson( \WP_Post $post, \WP_REST_Request $request, bool $creating ): void {
		$title = $request->get_param( 'title' );
		if ( $title ) {
			$title = sanitize_text_field( $title );
            wp_update_post([ 'ID' => $post->ID, 'post_title' => $title ]);
		}
	}

	public function prepare_lesson_item( WP_REST_Response $response, \WP_Post $post, WP_REST_Request $request ): WP_REST_Response {
		$data = $response->get_data();

		if ( ! isset( $data['title'] ) ) {
			$data['title']['rendered'] = get_the_title( $post );
            if ( $request->get_param('context') === 'edit' )
                $data['title']['raw'] = $post->post_title;
		}

		$response->set_data( $data );

		return $response;
	}
}
