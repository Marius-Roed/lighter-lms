<?php

namespace LighterLMS\API;

defined( 'ABSPATH' ) || exit;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

abstract class Base_Controller {


	protected string $namespace = 'lighterlms/v1';

	abstract public function register_routes(): void;

	protected function success( $data, int $status = 200 ): WP_REST_Response {
		return new WP_REST_Response( $data, $status );
	}

	protected function error( string $message, string $code, int $status = 400 ): WP_Error {
		return new WP_Error( $code, $message, array( 'status' => $status ) );
	}

	protected function get_post_or_error( int $id, string $post_type ): \WP_Post|WP_Error {
		$post = get_post( $id );
		if ( ! $post || $post->post_type !== $post_type ) {
			return $this->error(
				"The requested {$post_type} does not exist.",
				"{$post_type}_not_found",
				404
			);
		}
		return $post;
	}

	protected function format_post( \WP_Post $post ): array {
		return array(
			'id'         => (int) $post->ID,
			'slug'       => $post->post_name,
			'status'     => $post->post_status,
			'type'       => $post->post_type,
			'title'      => $post->post_title,
			'content'    => $post->post_content,
			'excerpt'    => $post->post_excerpt,
			'author'     => (int) $post->post_author,
			'date'       => $post->post_date,
			'modified'   => $post->post_modified,
			'menu_order' => (int) $post->menu_order,
		);
	}

	protected function sanitize_sort_order( $value ): int {
		return absint( $value );
	}
}
