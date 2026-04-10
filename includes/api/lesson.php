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

		register_rest_route(
			$this->namespace,
			'/lesson/updateOrder',
			array(
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'update_order' ),
					'permission_callback' => array( $this, 'read_lessons' ),
					'args'                => array(
						'to'   => array(
							'required' => true,
							'type'     => 'object',
						),
						'from' => array(
							'required' => false,
							'type'     => 'object',
						),
					),
				),
			)
		);
	}

	public function can_read( WP_REST_Request $request ): bool {
		return current_user_can( 'read_post', $request->get_param( 'id' ) );
	}

	public function read_lessons( WP_REST_Request $request ): bool {
		$to = $request->get_param( 'to' );
		if ( ! $to ) {
			return false;
		}

		$can_read = array_map( fn( $p ) => current_user_can( 'read_post', $p['id'] ), $to['reorder'] );
		return ! empty( $can_read ) && ! in_array( false, $can_read, strict: true );
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

	public function update_order( WP_REST_Request $request ): WP_Error|WP_REST_Response {
		$to_data   = $request->get_param( 'to' );
		$from_data = $request->get_param( 'from' );

		$reordered = lighter()->lms->topic->reorder_lessons( $to_data, $from_data );

		if ( is_wp_error( $reordered ) ) {
			return $reordered;
		}

		$course = lighter()->lms->course->get_topics( $reordered->course_id );

		return $this->success( array_map( fn( $t ) => lighter()->lms->topic->normalise_for_rest( $t, true ), $course ) );
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
			wp_update_post(
				array(
					'ID'         => $post->ID,
					'post_title' => $title,
				)
			);
		}
	}

	public function prepare_lesson_item( WP_REST_Response $response, \WP_Post $post, WP_REST_Request $request ): WP_REST_Response {
		$data = $response->get_data();

		if ( ! isset( $data['title'] ) ) {
			$data['title']['rendered'] = get_the_title( $post );
			if ( $request->get_param( 'context' ) === 'edit' ) {
				$data['title']['raw'] = $post->post_title;
			}
		}

		$response->set_data( $data );

		return $response;
	}
}
