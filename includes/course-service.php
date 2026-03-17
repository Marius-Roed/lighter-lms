<?php

namespace LighterLMS;

use Error;
use Exception;

defined( 'ABSPATH' ) || exit;

class Course_Service {
	public $post;

	public function __construct() {
		$this->post = new Course_Post();
	}

	public function create( array $data ): int {
		$data['post_type']   = $this->post->post_type;
		$data['post_status'] = 'draft';
		$post_id             = wp_insert_post( $data );
		return $post_id;
	}

	public function get( int $id ): ?\WP_Post {
		return get_post( $id );
	}

	public function save( int $id, array $data ): int {
		$data['ID'] = $id;
		return wp_update_post( $data );
	}

	public function delete( int $id ): void {
		$post = get_post( $id );
		if ( $post->post_type !== lighter_lms()->course_post_type ) {
			_doing_it_wrong( __FUNCTION__, "Called delete course, on post of type \"{$post->post_type}\"", '1.0.0' );
			return;
		}

		lighter()->lms->db->start_transaction();
		try {
			$topics = $this->get_topics( $id );
			foreach ( $topics as $topic ) {
				lighter()->lms->db->topics->delete( $topic['id'] );
			}

			$deleted = wp_delete_post( $id, true );

			if ( ! $deleted ) {
				throw new Error( "Could not delete post {$id}" );
			}

			lighter()->lms->db->commit();
		} catch ( \Throwable $e ) {
			lighter()->lms->db->rollback();
			// TODO: Show admin notice
		}
	}

	public function get_topics( int $id ): ?array {
		$post = get_post( $id );

		if ( $post->post_type !== lighter_lms()->course_post_type ) {
			_doing_it_wrong( __FUNCTION__, "Cannot get lighter topics on post of type \"{$post->post_type}\"", '1.0.0' );
			return null;
		}

		return lighter()->lms->db->topics->find_by_course( $id );
	}

	public function get_settings( int $id ): array {
		return array();
	}

	public function save_settings( int $id, array $data ): bool {
		throw new Exception( 'Not yet implemented' );
	}
}
